# Web applications

`AbstractWebApplication` adds the HTTP surface to `AbstractApplication`: request input, a PSR-7
response, header and body management, redirects, caching and compression.

## Constructor

```php
public function __construct(
    ?Input $input = null,               // defaults to new Input() — reads $_REQUEST
    ?Registry $config = null,           // defaults to an empty Registry
    ?WebClient $client = null,          // defaults to new WebClient() — reads $_SERVER
    ?ResponseInterface $response = null // defaults to a Laminas Diactoros Response
)
```

The constructor calls `loadSystemUris()`, so `uri.*` config keys are available immediately after
construction — see [Configuration](configuration.md#keys-the-package-writes).

## Input

```php
$input = $app->getInput();

$id    = $input->getUint('id');
$name  = $input->getString('name', '');
$raw   = $input->get('payload', null, 'raw');
$method = $input->getMethod();          // 'GET', 'POST', …
```

`Input` filters by default with the `cmd` filter. Always pass the filter you actually want; see the
Input package documentation for the list.

> `$input->files->get()` accepts a filter argument but **ignores it** — the returned file name is
> the raw, client supplied value. Never build a path from it without sanitising it yourself.

## The response body

```php
$app->setBody('<h1>Hello</h1>');    // replace
$app->prependBody('<!doctype html>');
$app->appendBody('<!-- done -->');

$content = $app->getBody();          // current body as a string
```

Each of these rewrites the PSR-7 response body stream. If the stream cannot be written,
`Joomla\Application\Exception\UnableToWriteBody` is thrown.

## Headers

```php
$app->setHeader('Content-Type', 'application/json', true);  // $replace = true
$app->setHeader('Set-Cookie', 'a=1');                        // appends, does not replace
$app->setHeader('Set-Cookie', 'b=2');

$app->getHeaders();     // [['name' => 'Content-Type', 'value' => 'application/json'], …]
$app->clearHeaders();   // remove all headers, fluent
$app->sendHeaders();    // send status line + headers now
```

`getHeaders()` returns a flat list of `['name' => …, 'value' => …]` pairs, one entry per value, not
a name-indexed map.

The status code is carried as a header named `Status`:

```php
$app->setHeader('Status', '404', true);
```

`respond()` adds one automatically from the PSR-7 response if none is present.

### Security headers

`respond()` sets `Content-Type`, the cache headers and `Status`. It does **not** set any of the
protective headers. Add them in a `BEFORE_RESPOND` listener so they apply to every response,
including error responses:

```php
$dispatcher->addListener(ApplicationEvents::BEFORE_RESPOND, static function ($event) {
    $app = $event->getApplication();

    $app->setHeader('X-Content-Type-Options', 'nosniff', true);
    $app->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin', true);
    $app->setHeader('X-Frame-Options', 'DENY', true);

    if ($app->isSslConnection()) {
        $app->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true);
    }
});
```

## The PSR-7 response

```php
$response = $app->getResponse();          // ResponseInterface
$app->setResponse($response->withStatus(201));
```

Because PSR-7 messages are immutable, `setHeader()`/`setBody()` replace the whole response object
internally. Hold on to the return value of `getResponse()` only for as long as you need it.

## Caching

Caching is off by default.

```php
$app->allowCache();       // read the current setting
$app->allowCache(true);   // enable
$app->allowCache(false);  // disable
```

With caching **disabled**, `respond()` sends:

```
Expires: Wed, 17 Aug 2005 00:00:00 GMT
Last-Modified: <now> GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
```

With caching **enabled**, it sends an `Expires` 15 minutes in the future and, if you set
`$app->modifiedDate` to a `DateTime`, a `Last-Modified` derived from it.

> `Cache-Control` is set with `$replace = false`, so a `Cache-Control` header you set yourself is
> **appended to**, not replaced. Call `clearHeaders()` or set the header after `respond()` has run
> if you need full control.

## Compression

Set the `gzip` configuration key and `execute()` will compress the body after `doExecute()`:

```php
$app->set('gzip', true);
```

`compress()` inspects `Accept-Encoding`, uses `gzip` or `deflate` accordingly, sets
`Content-Encoding`, and silently does nothing when headers have already been sent or no supported
encoding is offered. It is skipped entirely when `zlib.output_compression` or the `ob_gzhandler`
output handler is already active.

## Redirects

```php
$app->redirect('/login');                       // 303 See Other
$app->redirect('https://example.com/', 301);
```

`redirect()`:

1. expands a URL starting with `index.php` using `uri.base.full`,
2. strips everything after the first CR or LF (header injection guard),
3. turns a relative URL into an absolute one using `uri.request`,
4. sends `Status` and `Location`, or falls back to a JavaScript redirect if headers are already sent,
5. dispatches `before_respond`, calls `respond()`, dispatches `after_respond`,
6. calls `close()` — **execution stops here**.

Two things to know:

* **`redirect()` does not validate the target.** Any absolute URL with a scheme is accepted as-is.
  If the target comes from the request — a `return` parameter, for example — you have an open
  redirect unless you check it yourself:

  ```php
  $target = $app->getInput()->getString('return', '');
  $base   = $app->get('uri.base.full');

  if ($target === '' || !str_starts_with($target, $base)) {
      $target = $base;
  }

  $app->redirect($target);
  ```

* **The status code check is permissive.** The guard is
  `if (!is_int($status) && !$this->isRedirectState($status))`, so any integer passes — including
  `200`. Pass a real 3xx code.

## TLS detection

```php
if ($app->isSslConnection()) { … }
```

This reads `$_SERVER['HTTPS']` and the server port only. Behind a TLS terminating proxy it returns
`false` even though the client connection is encrypted. If you run behind a proxy, decide on the
basis of your own trusted-proxy handling instead, for example in `initialise()`:

```php
protected function initialise()
{
    $proto = $this->getInput()->server->getString('HTTP_X_FORWARDED_PROTO', '');

    if ($this->isTrustedProxy() && $proto === 'https') {
        $this->set('uri.scheme', 'https');
    }
}
```

## Client detection

`$app->client` is a `Joomla\Application\Web\WebClient` with lazily detected, read-only properties:

```php
$app->client->platform;        // WebClient::WINDOWS, ::ANDROID, …
$app->client->mobile;          // bool
$app->client->engine;          // WebClient::WEBKIT, ::BLINK, …
$app->client->browser;         // WebClient::CHROME, ::FIREFOX, …
$app->client->browserVersion;  // string
$app->client->language;        // from Accept-Language
$app->client->encoding;        // from Accept-Encoding
$app->client->robot;           // bool
```

Detection is user-agent pattern matching. Treat the result as a hint, never as a security control.

## Putting it together

```php
use Joomla\Application\AbstractWebApplication;

final class JsonApiApplication extends AbstractWebApplication
{
    protected function initialise()
    {
        $this->mimeType = 'application/json';
        $this->allowCache(false);
    }

    protected function doExecute()
    {
        $id = $this->getInput()->getUint('id');

        if ($id === 0) {
            $this->setHeader('Status', '400', true);
            $this->setBody(json_encode(['error' => 'id is required'], JSON_THROW_ON_ERROR));

            return;
        }

        $this->setBody(json_encode(['id' => $id], JSON_THROW_ON_ERROR));
    }
}
```
