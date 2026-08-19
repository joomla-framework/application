# Getting started

## Installation

```bash
composer require joomla/application
```

The package needs a PSR-7 implementation for the response object. The framework's own
applications use `laminas/laminas-diactoros`:

```bash
composer require laminas/laminas-diactoros
```

## The smallest possible application

`AbstractApplication` gives you configuration, a logger, an event dispatcher and the lifecycle.
You supply `doExecute()`:

```php
<?php

use Joomla\Application\AbstractApplication;

require __DIR__ . '/vendor/autoload.php';

final class HelloApplication extends AbstractApplication
{
    protected function doExecute()
    {
        echo 'Hello, ' . $this->get('name', 'world') . "\n";
    }
}

$app = new HelloApplication();
$app->set('name', 'Joomla');
$app->execute();
```

Run it:

```bash
$ php hello.php
Hello, Joomla
```

## The smallest possible web application

For HTTP, extend `AbstractWebApplication`. You write into a response body instead of echoing, and
`execute()` sends the response for you:

```php
<?php

use Joomla\Application\AbstractWebApplication;

require __DIR__ . '/vendor/autoload.php';

final class WebHelloApplication extends AbstractWebApplication
{
    protected function doExecute()
    {
        $this->setBody('<h1>Hello, world</h1>');
    }
}

(new WebHelloApplication())->execute();
```

`execute()` will:

1. dispatch `application.before_execute`,
2. call your `doExecute()`,
3. dispatch `application.after_execute`,
4. optionally gzip the body (if `gzip` is set in the configuration),
5. dispatch `application.before_respond`,
6. send status line, headers and body,
7. dispatch `application.after_respond`.

## Adding configuration

Configuration is a `Joomla\Registry\Registry`. Pass one in, or set values afterwards:

```php
use Joomla\Registry\Registry;

$config = new Registry([
    'debug' => true,
    'gzip'  => true,
    'db'    => [
        'driver' => 'mysqli',
        'host'   => 'localhost',
    ],
]);

$app = new WebHelloApplication(null, $config);

$app->get('db.driver');        // 'mysqli' — dot notation works
$app->get('missing', 'fallback');
```

See [Configuration](configuration.md) for the keys the package itself reads and writes.

## Adding events

Attach a dispatcher and you can hook into the lifecycle without subclassing:

```php
use Joomla\Application\ApplicationEvents;
use Joomla\Event\Dispatcher;

$dispatcher = new Dispatcher();
$dispatcher->addListener(
    ApplicationEvents::BEFORE_RESPOND,
    static function (ApplicationEvent $event) {
        $event->getApplication()->setHeader('X-Powered-By', 'Joomla Framework');
    }
);

$app->setDispatcher($dispatcher);
```

Without a dispatcher the application still works — `dispatchEvent()` returns `null` when none is
set. See [Lifecycle and events](lifecycle-and-events.md).

## Next steps

* A routed application with controllers: [Routing and controllers](routing-and-controllers.md)
* The full walkthrough, wiring in database, views, session and console:
  [Building a complete application](tutorial/index.md)
