# Updating from v2 to v3

Release 3.0.0 is a small upgrade. It raises the PHP requirement, moves the codebase to PSR-12, and
removes one response header. **No public or protected method signature changed**, so code written
against 2.x keeps working as long as it runs on PHP 8.1.

## At a glance

| | v2 (2.0.4) | v3 (3.0.0) |
|---|---|---|
| PHP | `^7.2.5` | `^8.1.0` |
| Public API | — | unchanged |
| Coding style | Joomla Coding Standard | PSR-12 |
| `X-Content-Encoded-By` header | sent when compressing | removed |

## Minimum supported PHP version raised

All Framework packages now require **PHP 8.1** or newer.

## The `X-Content-Encoded-By` header is no longer sent

`AbstractWebApplication::compress()` used to add a third header alongside the encoding headers:

```php
$this->setHeader('Content-Encoding', $encoding);
$this->setHeader('Vary', 'Accept-Encoding');
$this->setHeader('X-Content-Encoded-By', 'Joomla');   // removed in 3.0.0
```

The header carried no functional meaning and had been considered obsolete since 2013. It is simply
gone — compressed responses now carry `Content-Encoding` and `Vary` only.

This matters if you assert on response headers in tests, or if a downstream system reads
`X-Content-Encoded-By` to identify the application. Set it yourself if you still need it:

```php
$dispatcher->addListener(
    ApplicationEvents::BEFORE_RESPOND,
    static fn ($event) => $event->getApplication()->setHeader('X-Content-Encoded-By', 'Joomla')
);
```

## Codebase converted to PSR-12

The whole package was reformatted from the Joomla Coding Standard to PSR-12 (tabs to spaces, brace
placement, import ordering). This touches nearly every line but changes no behaviour.

Two practical consequences:

* A `git diff` between 2.x and 3.x is almost entirely noise. Use `git diff -w` or compare method
  signatures when looking for real changes.
* If you maintain patches against this package, expect all of them to conflict. Reapply rather than
  rebase.

The `joomla/coding-standards` dev dependency was replaced by `squizlabs/php_codesniffer` with a
PSR-12 ruleset.

## No API changes

Every `public` and `protected` method in `src/` has the same name and signature in 3.0.0 as in
2.0.4. The `Web\WebClient` constants are unchanged too. Upgrading is a matter of satisfying the PHP
and dependency requirements.

## Dependency changes

| Package | v2 (2.0.4) | v3 (3.0.0) |
|---|---|---|
| `php` | `^7.2.5` | `^8.1.0` |
| `joomla/event` | `^2.0` | `^3.0` |
| `joomla/registry` | `^1.4.5 \| ^2.0` | `^3.0` |
| `psr/log` | `^1.0` | `^1.0 \| ^2.0 \| ^3.0` |
| `psr/http-message` | `^1.0` | `^1.0` |
| `laminas/laminas-diactoros` | `^2.2.2` | `^2.24.0` |
| `symfony/deprecation-contracts` | `^2.1` | `^2 \| ^3` |

The optional packages in `suggest` moved to their 3.x releases: `joomla/controller`,
`joomla/input`, `joomla/router`, `joomla/session` and `joomla/uri` are all `^3.0`.
`psr/container` stays at `^1.0`.

## Deprecations still in place

The following were deprecated in 2.0 and **still work in 3.x**. Both were removed in 4.0.0 — see
[Updating from v3 to v4](v3-to-v4-update.md):

* Reading `$app->input` directly instead of calling `$app->getInput()`
* Passing a boolean as the `$status` argument of `redirect()`

Fixing them while still on 3.x makes the move to 4.0 a no-op.
