# Tutorial: building a complete application

This tutorial builds a small but complete web application on top of the Joomla Framework, using
`joomla/application` as the foundation and wiring in the other packages one at a time.

The application is a **note manager**: notes can be listed, read, created and deleted; creating and
deleting requires a login. It is deliberately small, but it exercises everything a real application
needs — dependency injection, configuration, routing, persistence, templating, sessions,
authentication, CSRF protection, events, error handling, logging and a console command.

## Chapters

1. [Project setup](01-project-setup.md) — dependencies, directory layout, front controller
2. [Container and configuration](02-container-and-configuration.md) — `joomla/di`, `joomla/registry`
3. [Routing and controllers](03-routing-and-controllers.md) — `joomla/router`, controller resolver
4. [Database and repositories](04-database-and-repositories.md) — `joomla/database`
5. [Views and templates](05-views-and-templates.md) — templating and escaping
6. [Session, authentication and CSRF](06-session-authentication-csrf.md) — `joomla/session`, `joomla/authentication`
7. [Events and error handling](07-events-and-error-handling.md) — `joomla/event`, logging
8. [Console commands and production](08-console-and-production.md) — `joomla/console`, deployment

## What you need

* PHP 8.3 or newer
* Composer
* SQLite (bundled with PHP) or MySQL

## The finished layout

```
notes/
├── bin/
│   └── console                 # CLI entry point
├── config/
│   └── app.dist.json           # configuration template
├── public/
│   ├── index.php               # web entry point
│   └── .htaccess               # front controller rewrite
├── src/
│   ├── Controller/
│   │   ├── AbstractController.php
│   │   ├── CreateNoteController.php
│   │   ├── DeleteNoteController.php
│   │   ├── ListNotesController.php
│   │   ├── LoginController.php
│   │   ├── LogoutController.php
│   │   └── ShowNoteController.php
│   ├── Command/
│   │   ├── CreateUserCommand.php
│   │   └── MigrateCommand.php
│   ├── EventListener/
│   │   ├── CsrfSubscriber.php
│   │   ├── ErrorSubscriber.php
│   │   └── SecurityHeadersSubscriber.php
│   ├── Repository/
│   │   ├── NoteRepository.php
│   │   └── UserRepository.php
│   ├── Service/
│   │   ├── AuthenticationProvider.php
│   │   ├── ConfigProvider.php
│   │   ├── DatabaseProvider.php
│   │   ├── EventProvider.php
│   │   ├── RouterProvider.php
│   │   ├── SessionProvider.php
│   │   ├── TemplateProvider.php
│   │   └── WebApplicationProvider.php
│   └── Template/
│       ├── PlatesRenderer.php
│       └── TemplateRendererInterface.php
├── templates/
│   ├── layout.php
│   ├── login.php
│   ├── notes.php
│   └── note.php
├── var/
│   ├── log/
│   └── notes.sqlite
├── bootstrap.php
└── composer.json
```

## A note on the framework's rough edges

The framework has a few behaviours that will bite you if you do not know about them. Rather than
writing code that quietly works around them, this tutorial points them out where they come up:

* Routing a pattern for more than one HTTP method — [chapter 3](03-routing-and-controllers.md#one-route-per-pattern)
* Base URI detection behind a proxy — [chapter 1](01-project-setup.md#pin-the-base-uri)
* Secure session defaults — [chapter 6](06-session-authentication-csrf.md#session-hardening)
* Session fixation after login — [chapter 6](06-session-authentication-csrf.md#regenerate-the-session-on-login)
* Escaping in templates — [chapter 5](05-views-and-templates.md#escaping-is-your-job)

Start with [Project setup](01-project-setup.md).
