# Updating from v1 to v2

Release 2.0.0 is the largest change in the package's history. It removes the CLI and daemon
application classes, introduces PSR-7 responses, adds the event lifecycle, and splits the
application contract into interfaces.

## At a glance

| | v1 (1.9.3) | v2 (2.0.0) |
|---|---|---|
| PHP | `^5.3.10 \| ^7.0 \| ^8.0` | `^7.2.5` |
| Application types | Web, CLI, Daemon | Web only |
| Response | Internal `stdClass` | PSR-7 `ResponseInterface` |
| Events | Comment placeholders only | Dispatched via `joomla/event` |
| Interfaces | none | four, see below |
| `joomla/input` | required | suggested |

## Minimum supported PHP version raised

All Framework packages now require **PHP 7.2.5** or newer.

## CLI and daemon classes removed

The following were removed with no replacement in this package:

* `Joomla\Application\AbstractCliApplication`
* `Joomla\Application\AbstractDaemonApplication`
* the entire `Joomla\Application\Cli` namespace — `CliInput`, `CliOutput`, `ColorProcessor`,
  `ColorStyle`, `Output\Stdout`, `Output\Xml`, `Output\Processor\ColorProcessor`,
  `Output\Processor\ProcessorInterface`

Use the [`joomla/console`](https://github.com/joomla-framework/console) package for command line
applications. It builds on `AbstractApplication`, so configuration, logging and events work the
same way.

There is no replacement for `AbstractDaemonApplication`. Its `pcntl` based process handling
(`daemonize()`, `fork()`, `detach()`, `restart()`, `stop()`, `writeProcessIdFile()`,
`loadConfiguration()` and the signal handlers) is gone entirely. Run long living processes under a
supervisor such as systemd or Supervisor instead.

## `AbstractApplication::__construct()` signature changed

The input object is no longer a concern of the base application class, so it was dropped from the
constructor:

```php
// v1
public function __construct(Input $input = null, Registry $config = null)

// v2
public function __construct(Registry $config = null)
```

If you extend `AbstractApplication` directly and call `parent::__construct($input, $config)`, the
config object is now silently passed as the input. Update the call.

`AbstractWebApplication::__construct()` keeps its input argument and gains a fourth one for the
response:

```php
// v1
public function __construct(Input $input = null, Registry $config = null, WebClient $client = null)

// v2
public function __construct(
    Input $input = null,
    Registry $config = null,
    WebClient $client = null,
    ResponseInterface $response = null
)
```

## `$input` property moved to the web application classes

The `$input` property moved from `AbstractApplication` to `AbstractWebApplication`. With
`joomla/console` handling console input differently, requiring every application to carry a
`joomla/input` object was no longer practical. `joomla/input` therefore moved from `require` to
`suggest` in `composer.json`.

Direct access to the property was deprecated in favour of the new `getInput()` method:

```php
// Deprecated in 2.0, still works via a magic getter
$app->input->getInt('id');

// Correct
$app->getInput()->getInt('id');
```

> The magic getter was announced for removal in 3.0 but actually survived the whole 3.x series. It
> was removed in **4.0.0** — see [Updating from v3 to v4](v3-to-v4-update.md).

## PSR-7 responses

The response is now a PSR-7 `ResponseInterface` instead of an internal `stdClass`. Two new methods
expose it:

```php
$response = $app->getResponse();
$app->setResponse($response->withStatus(201));
```

This causes one backwards incompatible change in the public API: **`getBody()` lost its
parameter.**

```php
// v1 — could return the internal array of body parts
public function getBody($asArray = false)

// v2 — always returns a string
public function getBody()
```

Replace `getBody(true)` with `getBody()` and split the string yourself if you relied on the array
form.

The package uses [Laminas Diactoros](https://github.com/laminas/laminas-diactoros) to build the
response. To use a different PSR-7 implementation, override `setHeader()`, `getHeaders()`,
`clearHeaders()`, `setBody()`, `prependBody()` and `appendBody()`.

## Application events

v1 marked the lifecycle with `// @event onBeforeExecute` comments and dispatched nothing. v2
dispatches real events through `joomla/event`, which became a required dependency.

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Event\Dispatcher;

$app->setDispatcher(new Dispatcher());
```

New classes:

* `Joomla\Application\ApplicationEvents` — the event name constants `BEFORE_EXECUTE`,
  `AFTER_EXECUTE`, `ERROR`, `BEFORE_RESPOND`, `AFTER_RESPOND`
* `Joomla\Application\Event\ApplicationEvent` — carries the application
* `Joomla\Application\Event\ApplicationErrorEvent` — carries the application and the `Throwable`

`AbstractApplication::execute()` now catches every `Throwable` from `doExecute()` and dispatches
`ApplicationEvents::ERROR` instead of letting it bubble up. **If you do not register a listener for
that event, an exception produces an empty response with status 200.** See
[Lifecycle and events](lifecycle-and-events.md#error-handling).

Attaching a dispatcher is optional: `dispatchEvent()` returns `null` when none is set.

## Controller resolvers

New in v2, for turning a route into a callable:

* `Joomla\Application\Controller\ControllerResolverInterface`
* `Joomla\Application\Controller\ControllerResolver`
* `Joomla\Application\Controller\ContainerControllerResolver` — resolves from a PSR-11 container

See [Routing and controllers](routing-and-controllers.md).

## A concrete web application

`Joomla\Application\WebApplication` is a minimal but functional web application. It extends
`AbstractWebApplication`, implements `SessionAwareWebApplicationInterface`, and dispatches a
request to a controller using a router and a controller resolver:

```php
$app = new WebApplication(
    new ContainerControllerResolver($container),
    $router,
    $input,
    $config
);

$app->execute();
```

## Session functionality moved to an interface and trait

Sessions are not mandatory for a web application, so the session methods moved out of
`AbstractWebApplication` into:

* `Joomla\Application\SessionAwareWebApplicationInterface` — extends `WebApplicationInterface`
* `Joomla\Application\SessionAwareWebApplicationTrait` — the implementation

The type hint also changed from the concrete class to the interface:

```php
// v1
public function setSession(Session $session)

// v2
public function setSession(SessionInterface $session)
```

## `checkToken()` now validates the token

`checkToken()` previously only checked whether the token was *present* in the request. It now
validates it. The redirect to `index.php` on a new session was removed — the method returns a
boolean and leaves the reaction to you:

```php
// v1 behaviour: could redirect and close the application
$app->checkToken();

// v2 behaviour: returns false, you decide
if (!$app->checkToken()) {
    $app->setHeader('Status', '403', true);
    $app->setBody('Invalid CSRF token.');

    return;
}
```

## Interfaces for application classes

The application contract is now expressed as four interfaces:

| Interface | Defines |
|---|---|
| `ApplicationInterface` | The base requirements for all applications |
| `ConfigurationAwareApplicationInterface` | An application aware of a configuration object |
| `WebApplicationInterface` | A web application handling HTTP requests and serving HTTP responses |
| `SessionAwareWebApplicationInterface` | A web application which requires session support |

Type your own services against these rather than against the abstract classes.

## Dependency changes

| Package | v1 (1.9.3) | v2 (2.0.0) |
|---|---|---|
| `php` | `^5.3.10 \| ^7.0 \| ^8.0` | `^7.2.5` |
| `joomla/input` | `^1.2` (required) | moved to `suggest` |
| `joomla/registry` | `^1.4.5 \| ^2.0` | `^1.4.5 \| ^2.0` |
| `psr/log` | `^1.0` | `^1.0` |
| `joomla/event` | — | `^2.0` (new) |
| `laminas/laminas-diactoros` | — | `^2.2.2` (new) |
| `psr/http-message` | — | `^1.0` (new) |
| `symfony/deprecation-contracts` | — | `^2.1` (new) |

New optional dependencies (`suggest`): `joomla/controller`, `joomla/input`, `joomla/router`,
`joomla/session`, `joomla/uri`, `psr/container`.
