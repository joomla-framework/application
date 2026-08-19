# Lifecycle and events

## The lifecycle

`AbstractApplication::execute()` is the base sequence:

```
dispatch application.before_execute
    doExecute()                       ← your code
dispatch application.after_execute
```

and if anything above throws a `Throwable`:

```
dispatch application.error
```

`AbstractWebApplication::execute()` extends it:

```
try {
    dispatch application.before_execute
        doExecute()                   ← your code
    dispatch application.after_execute
    compress()                        ← only when the `gzip` config key is truthy
} catch (Throwable) {
    dispatch application.error
}

dispatch application.before_respond
respond()                             ← status line, headers, body
dispatch application.after_respond
```

Two things follow from that shape:

* **The response is always sent.** `before_respond`, `respond()` and `after_respond` are outside the
  `try`, so they run even after an error. A listener on `application.error` can therefore still set
  a status code and a body, and the client will receive it.
* **A throw inside `respond()` is not caught.** Only `doExecute()` and `compress()` are protected.

## The events

All five names are constants on `Joomla\Application\ApplicationEvents`:

| Constant | Name | Payload | Dispatched |
|---|---|---|---|
| `BEFORE_EXECUTE` | `application.before_execute` | `ApplicationEvent` | Before `doExecute()` |
| `AFTER_EXECUTE` | `application.after_execute` | `ApplicationEvent` | After `doExecute()` returns normally |
| `ERROR` | `application.error` | `ApplicationErrorEvent` | When `doExecute()` or `compress()` throws |
| `BEFORE_RESPOND` | `application.before_respond` | `ApplicationEvent` | Web applications only, before the response is sent |
| `AFTER_RESPOND` | `application.after_respond` | `ApplicationEvent` | Web applications only, after the response is sent |

`ApplicationEvent::getApplication()` returns the `AbstractApplication`.
`ApplicationErrorEvent` adds `getError(): Throwable` and `setError(Throwable): void`.

## Attaching listeners

The application uses `Joomla\Event\DispatcherAwareTrait`, so a dispatcher is optional:

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Event\Dispatcher;

$dispatcher = new Dispatcher();

$dispatcher->addListener(
    ApplicationEvents::BEFORE_RESPOND,
    static function (ApplicationEvent $event): void {
        $event->getApplication()->setHeader('X-Frame-Options', 'DENY');
    }
);

$app->setDispatcher($dispatcher);
```

When no dispatcher has been set, `AbstractApplication::dispatchEvent()` catches the
`UnexpectedValueException` from `getDispatcher()` and returns `null`. Events are then simply not
dispatched — the application still runs.

## Subscribers

For anything beyond a one-liner, use a subscriber:

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

final class SecurityHeadersSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_RESPOND => ['onBeforeRespond', Priority::HIGH],
        ];
    }

    public function onBeforeRespond(ApplicationEvent $event): void
    {
        $app = $event->getApplication();

        $app->setHeader('X-Content-Type-Options', 'nosniff');
        $app->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $app->setHeader('X-Frame-Options', 'DENY');
    }
}

$dispatcher->addSubscriber(new SecurityHeadersSubscriber());
```

Listeners run in descending priority order. A listener that calls `$event->stopPropagation()`
prevents the remaining listeners for that event from running.

> A listener that throws inside `before_respond` or `after_respond` is **not** caught by
> `execute()` — those dispatches sit outside the `try`. Guard listeners that can fail.

## Error handling

The package dispatches `application.error` and does nothing else. There is no default error page,
no logging of the throwable, and no status code is set. Without a listener the client receives
whatever was in the response body at the time — usually nothing, with status 200.

A minimal but complete error listener:

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Router\Exception\MethodNotAllowedException;
use Joomla\Router\Exception\RouteNotFoundException;

$dispatcher->addListener(
    ApplicationEvents::ERROR,
    static function (ApplicationErrorEvent $event) use ($debug): void {
        $app   = $event->getApplication();
        $error = $event->getError();

        $app->getLogger()->error(
            $error->getMessage(),
            ['exception' => $error]
        );

        $status = match (true) {
            $error instanceof RouteNotFoundException     => 404,
            $error instanceof MethodNotAllowedException  => 405,
            default                                      => 500,
        };

        $app->setHeader('Status', (string) $status, true);
        $app->setBody(
            $debug
                ? '<pre>' . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . '</pre>'
                : '<h1>Something went wrong</h1>'
        );
    }
);
```

Note the `htmlspecialchars()` in the debug branch. Exception messages routinely contain request
data; writing them into the response unescaped is a reflected XSS vector. Never send the message
at all outside of debug mode — it also leaks paths, SQL fragments and class names.

## Logging

`AbstractApplication` implements `Psr\Log\LoggerAwareInterface`. `getLogger()` lazily installs a
`NullLogger` if none was set, so it is always safe to call:

```php
$app->setLogger(new Monolog\Logger('app'));

$app->getLogger()->info('Request handled', ['route' => $app->get('uri.route')]);
```

## Closing the application

`close($code = 0)` calls `exit($code)`. It is called by `redirect()` after the response has been
sent. Override it in tests so a test run does not terminate:

```php
final class TestApplication extends WebApplication
{
    public array $closed = [];

    public function close($code = 0): void
    {
        $this->closed[] = $code;
    }
}
```
