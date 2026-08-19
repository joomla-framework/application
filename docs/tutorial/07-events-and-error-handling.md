# 7. Events and error handling

## Why errors need a listener

`AbstractWebApplication::execute()` catches every `Throwable` from `doExecute()` and dispatches
`application.error`. That is all it does — it does not log, does not set a status code, and does
not produce a body.

Without a listener, an exception produces **HTTP 200 with an empty body**. That is the single most
important thing to wire up in a new application.

## The error subscriber

`src/EventListener/ErrorSubscriber.php`:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Exception\MethodNotAllowedException;
use Joomla\Router\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

final class ErrorSubscriber implements SubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::ERROR => 'onError',
        ];
    }

    public function onError(ApplicationErrorEvent $event): void
    {
        $app   = $event->getApplication();
        $error = $event->getError();

        $status = $this->statusFor($error);

        if ($status >= 500) {
            $this->logger->error($error->getMessage(), ['exception' => $error]);
        } else {
            $this->logger->notice($error->getMessage(), ['exception' => $error]);
        }

        $app->setHeader('Status', (string) $status, true);
        $app->setBody($this->render($status, $error));
    }

    private function statusFor(\Throwable $error): int
    {
        return match (true) {
            $error instanceof RouteNotFoundException    => 404,
            $error instanceof MethodNotAllowedException => 405,
            $error->getCode() === 403                   => 403,
            default                                     => 500,
        };
    }

    private function render(int $status, \Throwable $error): string
    {
        $titles = [
            403 => 'Forbidden',
            404 => 'Not found',
            405 => 'Method not allowed',
            500 => 'Something went wrong',
        ];

        $html = '<!doctype html><meta charset="utf-8"><title>'
            . htmlspecialchars($titles[$status] ?? 'Error', ENT_QUOTES, 'UTF-8')
            . '</title><h1>' . htmlspecialchars($titles[$status] ?? 'Error', ENT_QUOTES, 'UTF-8') . '</h1>';

        if ($this->debug) {
            // Escape: exception messages routinely contain request data.
            $html .= '<pre>' . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        return $html;
    }
}
```

Three points:

* **The message is only shown in debug mode, and even then escaped.** Exception messages contain
  file paths, class names, SQL fragments and — very often — values that came from the request.
  Writing one into the page unescaped is a reflected XSS; writing it at all in production is an
  information leak.
* **The status comes from the exception type.** `RouteNotFoundException` and
  `MethodNotAllowedException` come from `joomla/router`; anything else is a 500 unless it carries a
  code we recognise.
* **This runs inside the `try`-free part of the lifecycle.** `before_respond`, `respond()` and
  `after_respond` happen after the error event, so the body we set here is actually sent.

## Security headers

`respond()` sets `Content-Type`, cache headers and the status, and nothing protective. Add the rest
in a `BEFORE_RESPOND` listener so they apply to error responses too.

`src/EventListener/SecurityHeadersSubscriber.php`:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\WebApplicationInterface;
use Joomla\Event\SubscriberInterface;

final class SecurityHeadersSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_RESPOND => 'onBeforeRespond',
        ];
    }

    public function onBeforeRespond(ApplicationEvent $event): void
    {
        $app = $event->getApplication();

        if (!$app instanceof WebApplicationInterface) {
            return;
        }

        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('X-Frame-Options', 'DENY', true);
        $app->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin', true);
        $app->setHeader(
            'Content-Security-Policy',
            "default-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
            true
        );

        if ($app->isSslConnection()) {
            $app->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true);
        }
    }
}
```

`isSslConnection()` only inspects `$_SERVER['HTTPS']` and the port. Behind a TLS terminating proxy
it returns `false`, so HSTS would never be sent — decide from your own configuration instead:

```php
if (str_starts_with((string) $app->get('site_uri'), 'https://')) {
    $app->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true);
}
```

> A listener on `before_respond` or `after_respond` that throws is **not** caught — those dispatches
> sit outside the `try` in `execute()`. Keep these listeners simple and total.

## Using events for application logic

The same mechanism carries domain events. Give the dispatcher to your services and they can
announce what happened without knowing who listens:

```php
use Joomla\Event\Event;

final class NoteService
{
    public function __construct(
        private readonly NoteRepository $notes,
        private readonly DispatcherInterface $dispatcher
    ) {}

    public function create(string $title, string $body, int $userId): int
    {
        $id = $this->notes->create($title, $body);

        $this->dispatcher->dispatch(
            'note.created',
            new Event('note.created', ['id' => $id, 'userId' => $userId])
        );

        return $id;
    }
}
```

Two things to know about `joomla/event`:

* **`dispatch()` ignores its first argument when an event object is passed.** The listeners that
  run are those registered for `$event->getName()`, not for the `$name` parameter. Keep the two
  identical, as above, or the wrong listeners fire.
* **A listener that throws aborts the whole chain.** There is no error isolation. If a listener may
  fail, catch inside it.

## Ordering

Listeners run in descending priority. `Joomla\Event\Priority` provides
`MIN`, `LOW`, `NORMAL`, `HIGH`, `MAX`. Our CSRF check uses `HIGH` so it runs before anything else
on `before_execute`.

Within one priority, registration order decides — and subscribers are registered in the order of
the `event.subscriber` tag in `EventProvider`.

Next: [Console commands and production](08-console-and-production.md).
