# Joomla Application Package — Documentation

The Application package provides the skeleton that a PHP application runs inside: it owns the
configuration, the execution lifecycle, the event hooks around that lifecycle, and — for web
applications — the HTTP response.

## Guide

* [Overview](overview.md) — what the package does, and the class map
* [Getting started](getting-started.md) — a minimal running application
* [Configuration](configuration.md) — the config registry and the URI keys
* [Lifecycle and events](lifecycle-and-events.md) — `execute()`, the five events, error handling
* [Web applications](web-application.md) — response, headers, body, redirects, caching
* [Routing and controllers](routing-and-controllers.md) — `WebApplication` and controller resolvers
* [Session and CSRF](session-and-csrf.md) — session integration and form tokens
* [CLI applications](cli-applications.md) — using the package outside HTTP
* [Extending the package](extending.md) — writing your own application class
* [API reference](api-reference.md) — every public method, grouped by class

## Tutorial

* [Building a complete application](tutorial/index.md) — a step by step walkthrough that combines
  this package with the router, DI container, database, session and console packages.

## Upgrading

* [Updating from v1 to v2](v1-to-v2-update.md)
* [Updating from v2 to v3](v2-to-v3-update.md)
* [Updating from v3 to v4](v3-to-v4-update.md)
