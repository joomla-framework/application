# Extending the package

## Choosing a base class

Extend `AbstractApplication` when you have no HTTP response to produce, `AbstractWebApplication`
when you do but want to control dispatch yourself, and use `WebApplication` unchanged when routing
to controllers is what you need.

## `initialise()`

`AbstractApplication::__construct()` calls the protected `initialise()` hook at the end of the
constructor, after the configuration has been set. Override it for setup that belongs to the
application itself:

```php
final class MyApplication extends AbstractWebApplication
{
    protected function initialise()
    {
        $this->mimeType = $this->get('response.mime_type', 'text/html');

        date_default_timezone_set($this->get('timezone', 'UTC'));

        if ($this->get('debug')) {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');   // still never display, only log
        }
    }
}
```

Two caveats:

* `initialise()` runs **before** `AbstractWebApplication::__construct()` calls
  `loadSystemUris()`, so the `uri.*` keys are not available yet.
* It runs inside the constructor, so the object is not fully built. Do not call methods that rely
  on dependencies injected after construction (session, dispatcher, logger).

## Overriding `doExecute()`

This is the one abstract method:

```php
final class MicroApplication extends AbstractWebApplication
{
    /** @var callable[] */
    private array $routes = [];

    public function map(string $path, callable $handler): static
    {
        $this->routes[$path] = $handler;

        return $this;
    }

    protected function doExecute()
    {
        $route = '/' . trim((string) $this->get('uri.route'), '/');

        if (!isset($this->routes[$route])) {
            $this->setHeader('Status', '404', true);
            $this->setBody('Not found');

            return;
        }

        $this->setBody(($this->routes[$route])($this));
    }
}

(new MicroApplication())
    ->map('/', static fn () => 'Home')
    ->map('/about', static fn () => 'About')
    ->execute();
```

## Overriding `respond()`

`respond()` is protected, so you can wrap it — useful for adding headers that must be present on
every response including error responses, without relying on a dispatcher being configured:

```php
protected function respond()
{
    if (!$this->getResponse()->hasHeader('X-Content-Type-Options')) {
        $this->setHeader('X-Content-Type-Options', 'nosniff');
    }

    parent::respond();
}
```

## Making the application testable

Three methods make an application awkward to test. Override them in a test subclass:

```php
final class TestApplication extends WebApplication
{
    public array $headers = [];
    public array $closedWith = [];

    /** Collect instead of calling header() */
    protected function header($string, $replace = true, $code = null)
    {
        $this->headers[] = $string;
    }

    /** Never exit during a test run */
    public function close($code = 0)
    {
        $this->closedWith[] = $code;
    }

    /** Pretend headers have not been sent */
    protected function checkHeadersSent()
    {
        return false;
    }
}
```

With those in place you can assert on the response without output buffering:

```php
$app = new TestApplication($resolver, $router, new Input([]), new Registry(['site_uri' => 'https://example.test/']));
$app->execute();

$this->assertSame(200, $app->getResponse()->getStatusCode());
$this->assertStringContainsString('Hello', $app->getBody());
```

## Implementing the interfaces directly

If you do not want the abstract classes at all, the interfaces are small enough to implement
yourself. `ApplicationInterface` is two methods:

```php
interface ApplicationInterface
{
    public function close($code = 0);
    public function execute();
}
```

`ConfigurationAwareApplicationInterface` adds `get()`, `set()` and `setConfiguration()`.
`WebApplicationInterface` adds the HTTP surface listed in [API reference](api-reference.md).

Typing your own services against `ApplicationInterface` rather than against `AbstractApplication`
keeps them usable in both web and CLI contexts. Note that `ApplicationEvent::getApplication()`
returns `AbstractApplication`, not the interface, so event listeners are tied to the abstract class.
