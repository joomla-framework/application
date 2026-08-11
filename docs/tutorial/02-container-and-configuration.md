# 2. Container and configuration

`joomla/di` is a PSR-11 container with autowiring, aliases and tags. We use it to build every
service the application needs, so both the web and the console entry points can share the wiring.

## The bootstrap

`bootstrap.php`:

```php
<?php

declare(strict_types=1);

use App\Service\AuthenticationProvider;
use App\Service\ConfigProvider;
use App\Service\DatabaseProvider;
use App\Service\EventProvider;
use App\Service\TemplateProvider;
use App\Service\RouterProvider;
use App\Service\SessionProvider;
use App\Service\WebApplicationProvider;
use Joomla\DI\Container;

require __DIR__ . '/vendor/autoload.php';

$container = new Container();

$container->registerServiceProvider(new ConfigProvider(__DIR__));
$container->registerServiceProvider(new EventProvider());
$container->registerServiceProvider(new DatabaseProvider());
$container->registerServiceProvider(new SessionProvider());
$container->registerServiceProvider(new AuthenticationProvider());
$container->registerServiceProvider(new TemplateProvider(__DIR__ . '/templates'));
$container->registerServiceProvider(new RouterProvider());
$container->registerServiceProvider(new WebApplicationProvider());

return $container;
```

Order matters only where a provider reads another service **during registration**. Ours all use
closures, which are evaluated lazily on first `get()`, so registration order is free.

## Configuration provider

`src/Service/ConfigProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

final class ConfigProvider implements ServiceProviderInterface
{
    public function __construct(private readonly string $rootPath) {}

    public function register(Container $container): void
    {
        $container->share(
            Registry::class,
            function (): Registry {
                $file = $this->rootPath . '/config/app.json';

                // Registry::loadFile() returns an empty registry for an unreadable file
                // instead of failing, so check the path first.
                if (!is_readable($file)) {
                    throw new \RuntimeException(
                        sprintf('Configuration file "%s" is not readable.', $file)
                    );
                }

                $config = (new Registry())->loadFile($file, 'JSON');

                // Make relative paths absolute so the application works from any cwd.
                foreach (['database.database', 'log.path'] as $key) {
                    $value = (string) $config->get($key, '');

                    if ($value !== '' && !str_starts_with($value, '/') && !preg_match('#^[a-z]:#i', $value)) {
                        $config->set($key, $this->rootPath . '/' . $value);
                    }
                }

                $config->set('root_path', $this->rootPath);

                return $config;
            },
            true
        );

        $container->alias('config', Registry::class);
    }
}
```

Two details worth copying into your own projects:

* **Fail loudly on a missing configuration file.** `Registry::loadFile()` swallows read errors and
  hands you an empty registry, which means every security-relevant setting silently falls back to
  its default. The explicit `is_readable()` check turns that into a startup error.
* **Resolve relative paths once.** Otherwise the application only works when the current working
  directory happens to be the project root — which it is not under a web server.

## Logger provider

Bundle the logger into the event provider or give it its own; here it lives with the events since
that is where it is mostly consumed. `src/Service/EventProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\EventListener\CsrfSubscriber;
use App\EventListener\ErrorSubscriber;
use App\EventListener\SecurityHeadersSubscriber;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class EventProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            LoggerInterface::class,
            static function (Container $container): LoggerInterface {
                $config = $container->get(Registry::class);

                $logger = new Logger('app');
                $logger->pushHandler(
                    new StreamHandler(
                        $config->get('log.path'),
                        Level::fromName($config->get('log.level', 'warning'))
                    )
                );

                return $logger;
            },
            true
        );

        $container->share(
            DispatcherInterface::class,
            static function (Container $container): DispatcherInterface {
                $dispatcher = new Dispatcher();

                foreach ($container->getTagged('event.subscriber') as $subscriber) {
                    $dispatcher->addSubscriber($subscriber);
                }

                return $dispatcher;
            },
            true
        );

        // Subscribers are written in chapter 6 and 7.
        $container->share(CsrfSubscriber::class, static fn (Container $c) => new CsrfSubscriber());
        $container->share(SecurityHeadersSubscriber::class, static fn () => new SecurityHeadersSubscriber());
        $container->share(
            ErrorSubscriber::class,
            static fn (Container $c) => new ErrorSubscriber(
                $c->get(LoggerInterface::class),
                (bool) $c->get(Registry::class)->get('debug', false)
            )
        );

        $container->tag('event.subscriber', [
            CsrfSubscriber::class,
            SecurityHeadersSubscriber::class,
            ErrorSubscriber::class,
        ]);
    }
}
```

> **Tags are not inherited by child containers.** `Container::getTagged()` only looks at the
> container it is called on. If you ever call `createChild()`, resolve the dispatcher from the
> parent or re-tag in the child — otherwise the subscriber list comes back empty with no error.

## Application provider

`src/Service/WebApplicationProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Joomla\Application\Controller\ContainerControllerResolver;
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Application\WebApplication;
use Joomla\Application\WebApplicationInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Router\RouterInterface;
use Joomla\Session\SessionInterface;
use Psr\Log\LoggerInterface;

final class WebApplicationProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            ControllerResolverInterface::class,
            static fn (Container $c) => new ContainerControllerResolver($c),
            true
        );

        $container->share(Input::class, static fn () => new Input(), true);

        $container->share(
            WebApplication::class,
            static function (Container $container): WebApplication {
                $app = new WebApplication(
                    $container->get(ControllerResolverInterface::class),
                    $container->get(RouterInterface::class),
                    $container->get(Input::class),
                    $container->get(Registry::class)
                );

                $app->setDispatcher($container->get(DispatcherInterface::class));
                $app->setLogger($container->get(LoggerInterface::class));
                $app->setSession($container->get(SessionInterface::class));

                return $app;
            },
            true
        );

        $container->alias(WebApplicationInterface::class, WebApplication::class);
    }
}
```

The application is registered as **shared** so controllers, subscribers and repositories all see
the same instance — they have to, because they set headers and the body on it.

## Container gotchas

Two behaviours of `joomla/di` are worth knowing before you build on it:

* **An object with `__invoke()` registered as a value is treated as a factory.**
  `ContainerResource` checks `is_callable($value)`, so `$container->share(Foo::class, $invokableInstance)`
  calls the object instead of returning it. Wrap it: `$container->share(Foo::class, static fn () => $invokableInstance)`.
* **`extend()` fails on protected resources.** `Container::extend()` ends in `set()`, which throws
  `ProtectedKeyException` for anything registered with `protect()`. Register services you intend to
  decorate without protection.

## Verifying the wiring

```php
// scratch.php
$container = require __DIR__ . '/bootstrap.php';

var_dump($container->get(Joomla\Registry\Registry::class)->get('database.driver'));
var_dump($container->get(Psr\Log\LoggerInterface::class)::class);
var_dump(count($container->getTagged('event.subscriber')));
```

```bash
$ php scratch.php
string(6) "sqlite"
string(15) "Monolog\Logger"
int(3)
```

Next: [Routing and controllers](03-routing-and-controllers.md).
