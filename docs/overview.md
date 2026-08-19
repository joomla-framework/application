# Overview

The Application package provides the infrastructure to build and run PHP applications within the
Joomla! Framework.

It answers three questions for you, and deliberately nothing else:

1. **Where does configuration live?** In a `Joomla\Registry\Registry` owned by the application.
2. **What is the execution lifecycle?** `execute()` wraps your `doExecute()` in a fixed sequence of
   events, and turns any uncaught `Throwable` into an error event instead of a fatal.
3. **How is the response produced?** For web applications, a PSR-7 `ResponseInterface` that the
   application sends at the end of `execute()`.

Everything else — routing, templating, database access, authentication — comes from other framework
packages. The application is the place where you wire them together.

## Class map

```
ApplicationInterface                     close(), execute()
└── ConfigurationAwareApplicationInterface   + get(), set(), setConfiguration()
    └── AbstractApplication               abstract, owns config + logger + dispatcher
        └── AbstractWebApplication        abstract, owns input + client + PSR-7 response
            └── WebApplication            concrete, routes a request to a controller

WebApplicationInterface                  the HTTP surface (headers, body, redirect, …)
SessionAwareWebApplicationInterface      + getSession(), setSession(), checkToken(), getFormToken()
SessionAwareWebApplicationTrait          implementation of the above

ApplicationEvents                        the five event name constants
Event\ApplicationEvent                   carries the application
Event\ApplicationErrorEvent              carries the application and the Throwable

Controller\ControllerResolverInterface   resolve(ResolvedRoute): callable
Controller\ControllerResolver            resolves callables, invokables and ControllerInterface
Controller\ContainerControllerResolver   same, but pulls controllers from a PSR-11 container

Web\WebClient                            user agent detection
Exception\UnableToWriteBody              thrown when the response body cannot be written
```

## Which class do I extend?

| You are building | Extend | Notes |
|---|---|---|
| A web application with routing | *(nothing)* — use `WebApplication` | Give it a router and a controller resolver |
| A web application with custom dispatch | `AbstractWebApplication` | Implement `doExecute()` yourself |
| A CLI or worker process | `AbstractApplication` | You get config, logger, dispatcher, events; no HTTP |
| A console application | *(nothing)* — use `Joomla\Console\Application` | It builds on `AbstractApplication` |

## Dependencies

Required:

* `joomla/registry` — the configuration store
* `joomla/input` — request input (web applications)
* `joomla/uri` — URI handling for `loadSystemUris()` and `redirect()`
* `joomla/event` — the dispatcher used for lifecycle events
* `psr/log` — logging
* `psr/http-message` + a PSR-7 implementation — the response object

Optional, depending on which classes you use:

* `joomla/router` — required by `WebApplication`
* `joomla/session` — required by `SessionAwareWebApplicationTrait`
* `psr/container` — required by `ContainerControllerResolver`
* `joomla/controller` — the `ControllerInterface` that `ControllerResolver` looks for

## What this package intentionally does not do

Knowing the boundaries saves time:

* **No PSR-7 request.** The response is PSR-7, the request is `Joomla\Input\Input` reading from the
  superglobals. There is no `ServerRequestInterface` anywhere in the package.
* **No middleware.** The lifecycle is the five events listed in
  [Lifecycle and events](lifecycle-and-events.md); there is no PSR-15 pipeline.
* **No security headers.** `respond()` sets `Content-Type`, cache headers and the status. If you
  want `X-Content-Type-Options`, `Content-Security-Policy` or `Strict-Transport-Security`, set them
  yourself — see [Web applications](web-application.md#security-headers).
* **No trusted proxy handling.** `isSslConnection()` looks at `$_SERVER['HTTPS']` only; it does not
  consider `X-Forwarded-Proto`. Behind a TLS terminating proxy you must handle that yourself.
* **No error page.** The `ERROR` event is dispatched and that is all. If nothing listens, the
  request produces an empty response body. See [Error handling](lifecycle-and-events.md#error-handling).
