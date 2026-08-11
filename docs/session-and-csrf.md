# Session and CSRF

`SessionAwareWebApplicationTrait` implements `SessionAwareWebApplicationInterface` and gives an
application a session plus form-token handling. `WebApplication` already uses the trait.

## Attaching a session

```php
use Joomla\Session\Session;
use Joomla\Session\Storage\NativeStorage;
use Joomla\Session\Handler\FilesystemHandler;

$session = new Session(new NativeStorage(new FilesystemHandler(), [
    'name'             => 'app_session',
    'use_strict_mode'  => 1,       // reject client supplied session ids
    'cookie_httponly'  => 1,
    'cookie_secure'    => 1,       // set only when you serve over HTTPS
    'gc_maxlifetime'   => 1440,
]));

$app->setSession($session);
$session->start();
```

`getSession()` throws a `RuntimeException` if no session was set, so the application fails loudly
rather than silently skipping session-dependent code.

> The session package does **not** apply secure defaults on its own. `use_strict_mode`,
> `cookie_httponly` and `cookie_secure` all default to whatever `php.ini` says. Set them explicitly
> as shown above. `cookie_samesite` is not in the option allowlist of `NativeStorage::setOptions()`
> and has to be set with `ini_set('session.cookie_samesite', 'Lax')` before the session starts.

## Form tokens

The token is a random value stored in the session under `session.token`:

```php
$token = $app->getFormToken();        // creates one on first call
$token = $app->getFormToken(true);    // force a new one, e.g. after login
```

There are two ways to submit it, and `checkToken()` accepts both.

### HTML forms — the token is the field *name*

```php
<form method="post" action="/articles">
    <input type="hidden" name="<?= $app->getFormToken() ?>" value="1">
    …
</form>
```

The secret is the field name. An attacker who cannot read the page cannot guess it, so the request
cannot be forged.

### JavaScript — the token is the header *value*

```js
fetch('/articles', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
});
```

with the token rendered into the page:

```php
<meta name="csrf-token" content="<?= $app->getFormToken() ?>">
```

## Checking the token

```php
if (!$app->checkToken()) {
    $app->setHeader('Status', '403', true);
    $app->setBody('Invalid or missing CSRF token.');

    return;
}
```

`checkToken($method = 'post')`:

1. rejects any `$method` other than `post` or `get` with an `InvalidArgumentException`,
2. reads `X-CSRF-Token` unfiltered; if present, that value is the submitted token,
3. otherwise checks whether a request variable **named** like the session token exists,
4. compares the submitted value against the session token with `hash_equals()`,
5. asks the session about the result, which **expires the session** when the check fails,
6. returns the boolean.

Because a failed check expires the session, a forged request also invalidates the victim's session
rather than merely being rejected.

> **Changed behaviour.** Before this was fixed, the `X-CSRF-Token` path only tested that the header
> was *present*; its value was never compared to the session token. Any request carrying
> `X-CSRF-Token: a` passed the check. If you have code or tests that relied on that, they need
> updating — the header must now carry the real token.

## Where to check

Check on every state-changing request. The cleanest place is a `BEFORE_EXECUTE` listener so no
controller can forget:

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;

$dispatcher->addListener(
    ApplicationEvents::BEFORE_EXECUTE,
    static function (ApplicationEvent $event): void {
        /** @var \Joomla\Application\WebApplication $app */
        $app = $event->getApplication();

        if (\in_array($app->getInput()->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        if (!$app->checkToken()) {
            throw new \RuntimeException('Invalid CSRF token', 403);
        }
    }
);
```

The exception is caught by `execute()` and turned into an `application.error` event, where your
error listener renders the 403 — see [Lifecycle and events](lifecycle-and-events.md#error-handling).

## Session fixation

Regenerate the session id whenever the privilege level changes — most importantly right after a
successful login. Neither this package nor `joomla/authentication` does it for you:

```php
if ($authentication->authenticate() !== false) {
    $app->getSession()->fork(true);          // new id, destroy the old session
    $app->getSession()->set('user.id', $userId);
    $app->getFormToken(true);                // and a fresh CSRF token
}
```
