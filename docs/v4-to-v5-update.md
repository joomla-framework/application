# Updating from v4 to v5

Release 5.0.0 removes API that was deprecated in 4.x and adds native types where the 4.x API relied
on doc blocks. Most changes can be prepared while still on 4.x: fix the deprecation notices first,
then upgrade.

## At a glance

| | v4 (4.x) | v5 (5.0.0) |
|---|---|---|
| PHP | `^8.3.0` | unchanged |
| Native types on the web application API | doc blocks only | **parameter, return and property types** |
| JavaScript redirect for Internet Explorer | used for non-ASCII URLs | **removed** |

## Native types on the web application API

`AbstractWebApplication`, `WebApplicationInterface`, `SessionAwareWebApplicationInterface` and
`SessionAwareWebApplicationTrait` declare parameter and return types that were only documented
before. Classes that extend `AbstractWebApplication` or implement the interfaces must use compatible
signatures when they override these methods:

```php
// v4
public function redirect($url, $status = 303)
public function setHeader($name, $value, $replace = false)

// v5
public function redirect(string $url, int $status = 303): void
public function setHeader(string $name, string $value, bool $replace = false): static
```

The full list:

| Method | v5 signature |
|---|---|
| `redirect()` | `redirect(string $url, int $status = 303): void` |
| `allowCache()` | `allowCache(?bool $allow = null): bool` |
| `setHeader()` | `setHeader(string $name, string $value, bool $replace = false): static` |
| `getHeaders()` | `getHeaders(): array` |
| `clearHeaders()` / `sendHeaders()` | `(): static` |
| `setBody()` / `prependBody()` / `appendBody()` | `(string $content): static` |
| `getBody()` | `getBody(): string` |
| `isValidHttpStatus()` | `isValidHttpStatus(int $code): bool` |
| `isSslConnection()` | `isSslConnection(): bool` |
| `isAscii()` | `isAscii(string $str): bool` |
| `compress()` / `respond()` | `(): void` (protected) |
| `getHttpStatusValue()` | `getHttpStatusValue(int\|string $value): string` (protected) |
| `checkConnectionAlive()` / `checkHeadersSent()` | `(): bool` (protected) |
| `detectRequestUri()` | `detectRequestUri(): string` (protected) |
| `header()` | `header(string $string, bool $replace = true, ?int $code = null): void` (protected) |
| `isRedirectState()` | `isRedirectState(int\|string $state): bool` (protected) |
| `loadSystemUris()` | `loadSystemUris(?string $requestUri = null): void` (protected) |
| `getSession()` | `getSession(): SessionInterface` |
| `setSession()` | `setSession(SessionInterface $session): static` |
| `checkToken()` | `checkToken(string $method = 'post'): bool` |
| `getFormToken()` | `getFormToken(bool $forceNew = false): string` |

Callers are affected only in files with `declare(strict_types=1)`: there, `setHeader('Status', 404)`
must become `setHeader('Status', '404')`, and `setBody()` no longer accepts `null` or numbers.

The properties are typed as well and keep their visibility: `public string $charSet`,
`public string $mimeType`, `public string $httpVersion`, `public ?\DateTime $modifiedDate`,
`public WebClient $client`, `protected Input $input`, `protected ResponseInterface $response` and,
in the trait, `protected ?SessionInterface $session`. Assigning a value of another type now raises
a `TypeError`.

## JavaScript redirect for Internet Explorer removed

`redirect()` used to answer Internet Explorer (the Trident engine) with an HTML page containing a
JavaScript redirect when the target URL contained non-ASCII characters. Internet Explorer is out of
support since 2022; the redirect is now always sent as a `Location` header. The JavaScript fallback
for the case that headers have already been sent is unchanged.

## Dependency changes

None so far.
