# CLI applications

`AbstractApplication` has no HTTP dependency. It is a perfectly good base for cron jobs, workers
and one-off scripts, and it is what `joomla/console` builds on.

## A plain CLI application

```php
<?php

use Joomla\Application\AbstractApplication;
use Joomla\Registry\Registry;

require __DIR__ . '/vendor/autoload.php';

final class ImportApplication extends AbstractApplication
{
    protected function doExecute()
    {
        $file = $this->get('import.file');

        if (!is_readable($file)) {
            $this->getLogger()->error('Import file is not readable', ['file' => $file]);
            $this->close(1);
        }

        foreach (new SplFileObject($file) as $line) {
            // …
        }

        $this->getLogger()->info('Import finished');
    }
}

$app = new ImportApplication(new Registry(['import.file' => $argv[1] ?? '']));
$app->execute();
```

You still get the configuration registry, the PSR-3 logger, the event dispatcher and the
`before_execute`/`after_execute`/`error` events.

Note that `AbstractApplication::execute()` **swallows the throwable** — it turns it into an
`application.error` event and returns normally, with exit code 0. For a CLI process that is usually
wrong. Add a listener that sets the exit status:

```php
$dispatcher->addListener(
    ApplicationEvents::ERROR,
    static function (ApplicationErrorEvent $event): void {
        fwrite(STDERR, $event->getError()->getMessage() . PHP_EOL);
        $event->getApplication()->close(1);
    }
);
```

## Console applications

For anything with commands, arguments and options, use `joomla/console` instead of rolling your
own. `Joomla\Console\Application` extends `AbstractApplication`, so the configuration, logger and
event machinery documented here apply unchanged:

```php
use Joomla\Console\Application;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportCommand extends AbstractCommand
{
    protected static $defaultName = 'app:import';

    protected function configure(): void
    {
        $this->setDescription('Import records from a CSV file');
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');

        // …

        return 0;
    }
}

$console = new Application();
$console->addCommand(new ImportCommand());
$console->execute();
```

> `joomla/console` uses the `$defaultName` static property, which Symfony removed in 7.0 in favour
> of the `#[AsCommand]` attribute. Use `$defaultName` here — the attribute is not read.

## Sharing code between web and CLI

Put the shared wiring in a container and let each entry point build the application it needs:

```php
// bootstrap.php
return static function (): Joomla\DI\Container {
    $container = new Joomla\DI\Container();
    $container->registerServiceProvider(new ConfigProvider());
    $container->registerServiceProvider(new DatabaseProvider());
    $container->registerServiceProvider(new EventProvider());

    return $container;
};
```

```php
// public/index.php
$container = (require __DIR__ . '/../bootstrap.php')();
$container->get(Joomla\Application\WebApplication::class)->execute();
```

```php
// bin/console
$container = (require __DIR__ . '/../bootstrap.php')();
$container->get(Joomla\Console\Application::class)->execute();
```

The tutorial builds exactly this structure — see
[Building a complete application](tutorial/index.md).
