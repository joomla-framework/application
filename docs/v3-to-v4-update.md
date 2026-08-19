# Updating from v3 to v4

Release 4.0.0 raises the PHP requirement and removes the two backwards compatibility shims that
were deprecated in 2.0. Both removals are mechanical to fix, and both can be fixed while still on
3.x.

## At a glance

| | v3 (3.0.4) | v4 (4.0.0) |
|---|---|---|
| PHP | `^8.1.0` | `^8.3.0` |
| `$app->input` magic getter | deprecated, works | **removed** |
| Boolean `$status` in `redirect()` | deprecated, works | **removed** |
| `psr/http-message` | `^1.0` | `^2.0` |
| `laminas/laminas-diactoros` | `^2.24.0` | `^3.6.0` |

## Minimum supported PHP version raised

All Framework packages now require **PHP 8.3** or newer.

## Access to input data

`AbstractWebApplication::__get()` has been removed. It existed solely as a compatibility proxy that
mapped `$app->input` to `getInput()` and emitted a deprecation notice. Reading the property now
raises a PHP warning and evaluates to `null`, so the failure surfaces later as a call on `null`:

```php
// Old — removed in 4.0.0
$app->input->getInt('id');

// New
$app->getInput()->getInt('id');
```

Because the magic getter also handled every other property name with an `E_USER_NOTICE`, any other
undefined property read on the application now follows normal PHP semantics.

To find the call sites before upgrading:

```bash
grep -rn -- '->input->' src/
```

## Status on redirect

`redirect()` no longer accepts a boolean `$status`. In 1.x, `true` meant "301 Moved Permanently"
and `false` meant "303 See Other"; 2.0 deprecated that and 4.0 removed the translation. Pass the
HTTP status code:

```php
// Old — removed in 4.0.0
$app->redirect('/target', true);    // meant 301
$app->redirect('/target', false);   // meant 303

// New
$app->redirect('/target', 301);
$app->redirect('/target', 303);
```

The docblock type narrowed from `integer|boolean` to `integer` in both
`AbstractWebApplication::redirect()` and `WebApplicationInterface::redirect()`.

A boolean now fails loudly: the guard is
`if (!\is_int($status) && !$this->isRedirectState($status))`, and `isRedirectState()` casts to int
first, so `true` becomes `1`, fails the `> 299` test, and an `InvalidArgumentException` is thrown.

> Note that the same guard accepts **any** integer, including non-redirect codes — `redirect($url, 200)`
> passes and sends a `Location` header with status 200. Only booleans and other non-integers are
> rejected. Pass a real 3xx code.

## PSR-7 2.0

`psr/http-message` moved from `^1.0` to `^2.0`, and `laminas/laminas-diactoros` from `^2.24` to
`^3.6`.

PSR-7 2.0 adds return types to every interface method. This affects you if you **implement** any
PSR-7 interface yourself — a custom `ResponseInterface` passed into the application constructor,
or a decorator around one:

```php
// PSR-7 1.x
public function withStatus($code, $reasonPhrase = '')

// PSR-7 2.0
public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
```

Consuming a PSR-7 object needs no change. If you only pass Diactoros objects around, upgrading the
dependency is enough.

## Dependency changes

| Package | v3 (3.0.4) | v4 (4.0.0) |
|---|---|---|
| `php` | `^8.1.0` | `^8.3.0` |
| `psr/http-message` | `^1.0` | `^2.0` |
| `laminas/laminas-diactoros` | `^2.24.0` | `^3.6.0` |
| `joomla/event` | `^3.0` | `^4.0` |
| `joomla/registry` | `^3.0` | `^4.0` |
| `psr/log` | `^1.0 \| ^2.0 \| ^3.0` | unchanged |
| `symfony/deprecation-contracts` | `^2 \| ^3` | unchanged |

Optional packages in `suggest`:

| Package | v3 (3.0.4) | v4 (4.0.0) |
|---|---|---|
| `joomla/controller` | `^3.0` | `^4.0` |
| `joomla/input` | `^3.0` | `^4.0` |
| `joomla/router` | `^3.0` | `^4.0` |
| `joomla/session` | `^3.0` | `^4.0` |
| `joomla/uri` | `^3.0` | `^4.0` |
| `psr/container` | `^1.0` | `^2.0` |

`psr/container` 2.0 also adds return types (`has(string $id): bool`, `get(string $id): mixed`).
This matters only if you pass a hand-written container to `ContainerControllerResolver`.
