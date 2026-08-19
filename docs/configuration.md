# Configuration

The application owns a `Joomla\Registry\Registry` instance. Everything the application and its
listeners need to know about the environment lives there.

## Reading and writing

```php
$app->get('key');                 // null if unset
$app->get('key', 'default');
$app->get('database.host');       // dot notation traverses nested data

$previous = $app->set('key', 'value');   // returns the value that was there before
$app->setConfiguration($registry);       // replace the whole registry, fluent
```

`AbstractApplication::get()` delegates to `Registry::get()`. Two behaviours of the registry are
worth knowing because they surprise people:

* **An empty string reads back as the default.** `Registry::get()` treats `''` like "not set", so
  `$app->set('prefix', ''); $app->get('prefix', 'jos_');` returns `'jos_'`, not `''`. If you need to
  express "deliberately empty", use a sentinel value or read `$registry->exists()` instead.
* **The separator is `.`** by default. A key that itself contains a dot cannot be addressed.

## Supplying configuration

Pass a registry to the constructor:

```php
$app = new MyApplication(input: null, config: new Registry($data));
```

Or load it from a file — the registry supports JSON, INI, XML, YAML and PHP:

```php
use Joomla\Registry\Registry;

$config = new Registry();
$config->loadFile(__DIR__ . '/config/app.json', 'JSON');
```

> `Registry::loadFile()` does not check whether the file could be read. A missing or unreadable
> file yields an **empty registry, not an error**. Check the path yourself before loading, or the
> application will silently start with every setting on its default.

## Keys the package writes

These are set by the application itself; treat them as read-only.

| Key | Set by | Contents |
|---|---|---|
| `execution.datetime` | `AbstractApplication::__construct()` | `gmdate('Y-m-d H:i:s')` at boot |
| `execution.timestamp` | `AbstractApplication::__construct()` | `time()` at boot |
| `execution.microtimestamp` | `AbstractApplication::__construct()` | `microtime(true)` at boot |
| `uri.request` | `loadSystemUris()` | Full URI of the current request |
| `uri.base.full` | `loadSystemUris()` | Scheme, host, port and base path, with trailing slash |
| `uri.base.host` | `loadSystemUris()` | Scheme, host and port only |
| `uri.base.path` | `loadSystemUris()` | Base path only, with trailing slash |
| `uri.route` | `loadSystemUris()` | `uri.request` minus `uri.base.full` — this is what you route on |
| `uri.media.full` | `loadSystemUris()` | Absolute URI of the media folder |
| `uri.media.path` | `loadSystemUris()` | Path of the media folder |

`uri.route` is only set when `uri.request` actually starts with `uri.base.full`. Behind a rewriting
proxy that changes the path, it can be missing — `WebApplication::doExecute()` then routes on `null`.

## Keys the package reads

| Key | Read by | Effect |
|---|---|---|
| `gzip` | `AbstractWebApplication::execute()` | When truthy, and neither `zlib.output_compression` nor `ob_gzhandler` is active, the body is compressed with gzip or deflate depending on `Accept-Encoding` |
| `site_uri` | `loadSystemUris()` | Overrides base URI detection. Set this whenever the application runs behind a proxy or in a sub-directory — detection uses `SCRIPT_NAME`/`PHP_SELF` and gets it wrong often enough to be worth pinning |
| `media_uri` | `loadSystemUris()` | Overrides the media folder location. A value containing `://` is used verbatim, otherwise it is treated as a path below `uri.base.host` |

## Public properties that act as configuration

`AbstractWebApplication` exposes four public properties rather than config keys. They are read
during `respond()`:

```php
$app->charSet     = 'utf-8';      // appended to the Content-Type header
$app->mimeType    = 'text/html';  // the Content-Type header
$app->httpVersion = '1.1';        // used in the status line
$app->modifiedDate = new DateTime('2026-01-01');  // Last-Modified, only when caching is allowed
```

Set `mimeType` before the response is sent, for example in a controller:

```php
$app->mimeType = 'application/json';
$app->setBody(json_encode($data, JSON_THROW_ON_ERROR));
```

## A worked example

```php
use Joomla\Registry\Registry;

$configFile = __DIR__ . '/config/app.json';

if (!is_readable($configFile)) {
    throw new RuntimeException(sprintf('Configuration file "%s" is not readable.', $configFile));
}

$config = (new Registry())->loadFile($configFile, 'JSON');

// Pin the base URI instead of relying on detection.
$config->set('site_uri', 'https://example.com/app/');
$config->set('media_uri', 'https://cdn.example.com/assets/');
$config->set('gzip', true);

$app = new MyApplication(null, $config);
```
