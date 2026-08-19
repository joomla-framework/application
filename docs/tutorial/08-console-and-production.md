# 8. Console commands and production

## The console entry point

`bin/console`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Command\CreateUserCommand;
use App\Command\MigrateCommand;
use Joomla\Console\Application;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Psr\Log\LoggerInterface;

$container = require dirname(__DIR__) . '/bootstrap.php';

$console = new Application(null, null, $container->get(Registry::class));
$console->setDispatcher($container->get(DispatcherInterface::class));
$console->setLogger($container->get(LoggerInterface::class));

$console->addCommand(new MigrateCommand($container->get(DatabaseInterface::class)));
$console->addCommand(new CreateUserCommand($container->get(DatabaseInterface::class)));

$console->execute();
```

```bash
chmod +x bin/console
```

`Joomla\Console\Application` extends `AbstractApplication`, so the configuration registry, the
logger and the event dispatcher work exactly as they do on the web side — the same container builds
both.

## A migration command

`src/Command/MigrateCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MigrateCommand extends AbstractCommand
{
    protected static $defaultName = 'db:migrate';

    public function __construct(private readonly DatabaseInterface $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create the database schema');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migrating');

        $statements = [
            'CREATE TABLE IF NOT EXISTS ' . $this->db->quoteName('#__notes') . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                created_at TEXT NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS ' . $this->db->quoteName('#__users') . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL
            )',
        ];

        foreach ($statements as $sql) {
            $this->db->setQuery($sql)->execute();
        }

        $io->success('Schema is up to date.');

        return 0;
    }
}
```

> `joomla/console` reads the `$defaultName` static property. Symfony removed that mechanism in 7.0
> in favour of `#[AsCommand]`, and the attribute is **not** read here — use `$defaultName`.

## A command that handles a secret

`src/Command/CreateUserCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Joomla\Authentication\Password\BCryptHandler;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CreateUserCommand extends AbstractCommand
{
    protected static $defaultName = 'user:create';

    public function __construct(private readonly DatabaseInterface $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create a user');
        $this->addArgument('username', InputArgument::REQUIRED, 'The username');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $username = (string) $input->getArgument('username');

        // Never take a password as a command line argument: the full command line is
        // visible to every local user via `ps` and lands in the shell history.
        $password = $io->askHidden('Password');

        if (!\is_string($password) || strlen($password) < 12) {
            $io->error('The password must be at least 12 characters long.');

            return 1;
        }

        $hash = (new BCryptHandler())->hashPassword($password, ['cost' => 12]);

        $query = $this->db->createQuery()
            ->insert($this->db->quoteName('#__users'))
            ->columns($this->db->quoteName(['username', 'password']))
            ->values(':username, :password')
            ->bind(':username', $username)
            ->bind(':password', $hash);

        $this->db->setQuery($query)->execute();

        $io->success(sprintf('Created user "%s".', $username));

        return 0;
    }
}
```

```bash
$ php bin/console db:migrate
$ php bin/console user:create alice
 Password: ****
 [OK] Created user "alice".
```

## Long running commands

`joomla/console` has no signal handling and no lock. For a worker or a cron command, add both:

```php
protected function doExecute(InputInterface $input, OutputInterface $output): int
{
    $lock = fopen(sys_get_temp_dir() . '/notes-worker.lock', 'c');

    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        $output->writeln('Another instance is running.');

        return 0;
    }

    $running = true;

    if (\function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, static function () use (&$running) { $running = false; });
        pcntl_signal(SIGINT, static function () use (&$running) { $running = false; });
    }

    while ($running) {
        // … one unit of work, then check $running again
    }

    flock($lock, LOCK_UN);

    return 0;
}
```

Without the signal handler a deployment that sends `SIGTERM` kills the process in the middle of a
job.

## Production checklist

**Configuration**

- [ ] `debug` is `false` — the error subscriber only prints exception details in debug mode
- [ ] `site_uri` is pinned to the real public URL, with a trailing slash
- [ ] `config/app.json` is not readable over HTTP and not in version control

**Filesystem**

- [ ] Only `public/` is the document root
- [ ] `var/` is writable by the web server user and not served
- [ ] The SQLite file (or database credentials) is outside the document root

**Session**

- [ ] `use_strict_mode` is on
- [ ] `cookie_secure` is on when serving over HTTPS
- [ ] `cookie_httponly` is on
- [ ] `session.cookie_samesite` is set — it cannot be set through `NativeStorage::setOptions()`
- [ ] The session id is regenerated on login (`fork(true)`)

**HTTP**

- [ ] The security headers subscriber is registered
- [ ] HSTS is decided from configuration, not from `isSslConnection()`, if you run behind a proxy
- [ ] Every redirect target is built from `uri.base.full` — `redirect()` validates nothing

**Application**

- [ ] An `application.error` listener is registered — otherwise exceptions produce HTTP 200 with an
      empty body
- [ ] The CSRF subscriber covers every non-safe method
- [ ] Logs go somewhere you actually read

**Deployment**

```bash
composer install --no-dev --optimize-autoloader
php bin/console db:migrate
```

## Where to go next

* [API reference](../api-reference.md) — the full method list
* [Extending the package](../extending.md) — custom application classes and testing
* [Session and CSRF](../session-and-csrf.md) — the token mechanism in detail

The framework packages used here each have their own `docs/` directory with the same structure.
