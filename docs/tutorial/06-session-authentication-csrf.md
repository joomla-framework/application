# 6. Session, authentication and CSRF

## Session hardening

`joomla/session` does not apply secure defaults — `use_strict_mode`, `cookie_httponly` and
`cookie_secure` all fall back to whatever `php.ini` says, and PHP's own defaults are not what you
want. Set them explicitly.

`src/Service/SessionProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;
use Joomla\Session\Handler\FilesystemHandler;
use Joomla\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Session\Storage\NativeStorage;

final class SessionProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            SessionInterface::class,
            static function (Container $container): SessionInterface {
                $config = $container->get(Registry::class);
                $secure = str_starts_with((string) $config->get('site_uri'), 'https://');

                // SameSite is not in NativeStorage::setOptions()'s allowlist, so it has to be
                // set directly before the session starts.
                ini_set('session.cookie_samesite', 'Lax');

                $storage = new NativeStorage(new FilesystemHandler(), [
                    'name'            => $config->get('session.name', 'app_session'),
                    'use_strict_mode' => 1,   // reject client supplied session ids
                    'use_only_cookies' => 1,
                    'cookie_httponly' => 1,
                    'cookie_secure'   => $secure ? 1 : 0,
                    'cookie_path'     => '/',
                    'gc_maxlifetime'  => (int) $config->get('session.expire', 3600),
                ]);

                return new Session($storage);
            },
            true
        );
    }
}
```

`use_strict_mode` is the important one. Without it PHP accepts a session id supplied by the client
and creates a session under it — which is exactly what session fixation needs.

Unknown option keys are **discarded silently** by `NativeStorage::setOptions()`, and the method
returns early once the session is active. Set options before starting the session, and verify with
`ini_get('session.use_strict_mode')` if something appears not to apply.

## Starting the session

The session has to be started before anything reads from it. A `BEFORE_EXECUTE` listener is the
natural place, but for an application this size starting it in the provider is simpler — as long as
you accept that every request gets a session. We start it in the CSRF subscriber below, which runs
first anyway.

## Authentication

`src/Service/AuthenticationProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Joomla\Authentication\Authentication;
use Joomla\Authentication\Password\BCryptHandler;
use Joomla\Authentication\Strategies\DatabaseStrategy;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Input\Input;

final class AuthenticationProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            Authentication::class,
            static function (Container $container): Authentication {
                $authentication = new Authentication();

                $authentication->addStrategy(
                    'database',
                    new DatabaseStrategy(
                        $container->get(Input::class),
                        $container->get(DatabaseInterface::class),
                        [
                            'database_table'  => '#__users',
                            'username_column' => 'username',
                            'password_column' => 'password',
                        ],
                        new BCryptHandler()
                    )
                );

                return $authentication;
            },
            true
        );
    }
}
```

`DatabaseStrategy` reads `username` and `password` from the input itself and uses a prepared
statement for the lookup, so there is nothing to bind manually.

Two things it does **not** do, which you have to add:

* **It does not check account state.** The query selects the password column and nothing else, so a
  blocked or unverified account authenticates like any other. If you add a `blocked` column, check
  it after `authenticate()` returns.
* **It reveals whether a username exists.** `getResults()` distinguishes `NO_SUCH_USER` from
  `INVALID_CREDENTIALS`, and an unknown user is rejected without a password hash being computed,
  so the response is also measurably faster. Log the detail, show the user one message.

## The login controller

`src/Controller/LoginController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Template\TemplateRendererInterface;
use Joomla\Application\WebApplicationInterface;
use Joomla\Authentication\Authentication;

final class LoginController extends AbstractController
{
    public function __construct(
        WebApplicationInterface $app,
        private readonly Authentication $authentication,
        private readonly UserRepository $users,
        private readonly TemplateRendererInterface $renderer
    ) {
        parent::__construct($app);
    }

    public function execute(): bool
    {
        return match ($this->input()->getMethod()) {
            'GET'   => $this->showForm(),
            'POST'  => $this->attemptLogin(),
            default => $this->methodNotAllowed(),
        };
    }

    private function showForm(string $error = ''): bool
    {
        return $this->respond(
            $this->renderer->render('login', ['error' => $error]),
            $error === '' ? 200 : 401
        );
    }

    private function attemptLogin(): bool
    {
        $username = $this->authentication->authenticate();

        if ($username === false) {
            // Log the detail, tell the user nothing specific.
            $this->app->getLogger()->notice(
                'Failed login attempt',
                ['results' => $this->authentication->getResults()]
            );

            return $this->showForm('Invalid username or password.');
        }

        $user = $this->users->findByUsername($username);

        $this->startUserSession($user);

        return $this->redirect('/');
    }

    private function startUserSession(object $user): void
    {
        $session = $this->app->getSession();

        // Session fixation: the id the visitor arrived with must not survive the
        // privilege change. Nothing in the framework does this for you.
        $session->fork(true);

        $session->set('user', ['id' => (int) $user->id, 'username' => $user->username]);

        // A new session means a new CSRF token.
        $this->app->getFormToken(true);
    }
}
```

### Regenerate the session on login

The `fork(true)` call above is not optional. `joomla/authentication` and `joomla/session` know
nothing about each other: authentication never touches the session, and the session never learns
that a login happened. Without the regeneration, an attacker who can set a session cookie in the
victim's browser before login keeps a valid session afterwards.

## CSRF protection

Rather than remembering `checkToken()` in every controller, enforce it centrally.

`src/EventListener/CsrfSubscriber.php`:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\SessionAwareWebApplicationInterface;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

final class CsrfSubscriber implements SubscriberInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => ['onBeforeExecute', Priority::HIGH],
        ];
    }

    public function onBeforeExecute(ApplicationEvent $event): void
    {
        $app = $event->getApplication();

        if (!$app instanceof SessionAwareWebApplicationInterface) {
            return;
        }

        $app->getSession()->start();

        if (\in_array($app->getInput()->getMethod(), self::SAFE_METHODS, true)) {
            return;
        }

        if (!$app->checkToken()) {
            throw new \RuntimeException('Invalid CSRF token', 403);
        }
    }
}
```

The exception is caught by `AbstractWebApplication::execute()` and turned into an
`application.error` event, where the error subscriber from
[chapter 7](07-events-and-error-handling.md) renders it.

### How the token is submitted

Two forms, both accepted by `checkToken()`:

* **HTML forms** put the token in the field *name*:
  `<input type="hidden" name="<?= $token ?>" value="1">`
* **JavaScript** puts it in the `X-CSRF-Token` header *value*.

```js
fetch(base + 'notes/create', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({ title, body }),
});
```

Both are compared against the session token with `hash_equals()`, and a failed check expires the
session.

> If you are working with an older checkout: the header path used to check only that the header was
> *present*, never its value, so `X-CSRF-Token: a` passed. Make sure you are on a version where
> `checkToken()` compares the value.

## The logout controller

```php
<?php

declare(strict_types=1);

namespace App\Controller;

final class LogoutController extends AbstractController
{
    public function execute(): bool
    {
        $this->app->getSession()->destroy();

        return $this->redirect('/');
    }
}
```

The route is `POST /logout` and the form carries the token, so the CSRF subscriber protects it —
a logout reachable by `GET` can be triggered from any page on the internet.

Next: [Events and error handling](07-events-and-error-handling.md).
