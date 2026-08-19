# 1. Project setup

## Dependencies

```bash
mkdir notes && cd notes
composer init --name=example/notes --no-interaction

composer require \
    joomla/application \
    joomla/di \
    joomla/router \
    joomla/registry \
    joomla/input \
    joomla/database \
    joomla/session \
    joomla/authentication \
    joomla/event \
    joomla/console \
    laminas/laminas-diactoros \
    league/plates \
    monolog/monolog
```

`laminas/laminas-diactoros` supplies the PSR-7 response, `league/plates` the template engine, and
`monolog/monolog` the PSR-3 logger. None of the three is mandated by the framework — swap in
whatever you prefer. The framework has no templating package of its own that is worth building on
today, so the template engine is a direct dependency; see
[chapter 5](05-views-and-templates.md).

Add the autoloader mapping to `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "config": {
        "sort-packages": true
    }
}
```

```bash
composer dump-autoload
```

## Directory layout

```bash
mkdir -p bin config public src/{Controller,Command,EventListener,Repository,Service,Template} templates var/log
```

Only `public/` is served by the web server. Everything else — configuration, source, logs, the
SQLite file — stays above the document root.

## Configuration template

`config/app.dist.json`:

```json
{
    "debug": false,
    "site_uri": "http://localhost:8000/",
    "gzip": true,
    "timezone": "UTC",
    "database": {
        "driver": "sqlite",
        "database": "var/notes.sqlite",
        "prefix": "notes_"
    },
    "session": {
        "name": "notes_session",
        "expire": 3600
    },
    "log": {
        "path": "var/log/app.log",
        "level": "warning"
    }
}
```

Copy it to `config/app.json` and adjust. Keep `app.json` out of version control; commit only the
`.dist` template.

## The front controller

`public/index.php`:

```php
<?php

declare(strict_types=1);

use Joomla\Application\WebApplication;

$container = require dirname(__DIR__) . '/bootstrap.php';

$container->get(WebApplication::class)->execute();
```

That is the whole entry point. Everything it needs is built in `bootstrap.php`, which we write in
the [next chapter](02-container-and-configuration.md).

## Rewrite rules

`public/.htaccess` for Apache:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

For nginx:

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

For local development the built-in server is enough:

```bash
php -S localhost:8000 -t public
```

## Pin the base URI

`AbstractWebApplication::loadSystemUris()` derives the base URI from `SCRIPT_NAME` or `PHP_SELF`.
That detection is guesswork: it breaks behind a reverse proxy, in a sub-directory, and with some
CGI configurations. When it is wrong, `uri.route` is wrong and **every route stops matching** —
with no error message that points at the cause.

Set `site_uri` explicitly and the detection is skipped:

```json
{
    "site_uri": "https://notes.example.com/"
}
```

Include the trailing slash, and include the sub-path if the application does not live at the domain
root (`https://example.com/notes/`).

While you are debugging routing, dump the derived values — they are ordinary configuration keys:

```php
var_dump(
    $app->get('uri.request'),
    $app->get('uri.base.full'),
    $app->get('uri.route')     // this is what the router sees
);
```

## Directory permissions

`var/` needs to be writable by the web server user, and it must not be reachable over HTTP:

```bash
chmod 750 var var/log
```

If you cannot place `var/` outside the document root, deny access to it in the web server
configuration. The SQLite database and the log file both contain data you do not want served.

Next: [Container and configuration](02-container-and-configuration.md).
