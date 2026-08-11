# 3. Routing and controllers

## The router provider

`src/Service/RouterProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Controller\CreateNoteController;
use App\Controller\DeleteNoteController;
use App\Controller\ListNotesController;
use App\Controller\LoginController;
use App\Controller\LogoutController;
use App\Controller\ShowNoteController;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Router\Router;
use Joomla\Router\RouterInterface;

final class RouterProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            RouterInterface::class,
            static function (): RouterInterface {
                $router = new Router();

                $router->get('/', ListNotesController::class);
                $router->get('/notes/:id', ShowNoteController::class, ['id' => '\d+']);

                // See "One route per pattern" below for why these use all().
                $router->all('/notes/create', CreateNoteController::class);
                $router->all('/notes/:id/delete', DeleteNoteController::class, ['id' => '\d+']);
                $router->all('/login', LoginController::class);
                $router->post('/logout', LogoutController::class);

                return $router;
            },
            true
        );
    }
}
```

## One route per pattern

`Router::parseRoute()` walks the routes in registration order and throws `MethodNotAllowedException`
as soon as it finds a **pattern** match whose method does not fit — it never looks at later routes.
So this does not work:

```php
$router->get('/notes/create', ShowFormController::class);
$router->post('/notes/create', SaveNoteController::class);   // unreachable
```

A `POST /notes/create` matches the first route's pattern, sees `GET`, and throws 405.

Until that is fixed upstream, register the pattern once with `all()` and branch inside the
controller:

```php
public function execute(): bool
{
    return match ($this->app->getInput()->getMethod()) {
        'GET'  => $this->showForm(),
        'POST' => $this->save(),
        default => $this->methodNotAllowed(),
    };
}
```

Routes that only ever answer one verb — `GET /`, `GET /notes/:id`, `POST /logout` — can keep the
specific method, as long as no other route shares the pattern.

## A base controller

Controllers need the application often enough to justify a small base class.
`src/Controller/AbstractController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Joomla\Application\WebApplicationInterface;
use Joomla\Controller\ControllerInterface;
use Joomla\Input\Input;

abstract class AbstractController implements ControllerInterface
{
    public function __construct(protected readonly WebApplicationInterface $app) {}

    protected function input(): Input
    {
        return $this->app->getInput();
    }

    protected function respond(string $html, int $status = 200): bool
    {
        $this->app->setHeader('Status', (string) $status, true);
        $this->app->setBody($html);

        return $status < 400;
    }

    protected function redirect(string $path): bool
    {
        $this->app->redirect($this->app->get('uri.base.full') . ltrim($path, '/'));

        return true;   // not reached: redirect() calls close()
    }

    protected function methodNotAllowed(): bool
    {
        $this->app->setHeader('Status', '405', true);
        $this->app->setHeader('Allow', 'GET, POST', true);
        $this->app->setBody('Method not allowed');

        return false;
    }
}
```

`redirect()` always builds an absolute URL from `uri.base.full`. Do this consistently and you never
have to think about open redirects — `AbstractWebApplication::redirect()` performs **no validation
at all** on the target, so any URL that reaches it is sent verbatim.

## Listing notes

`src/Controller/ListNotesController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\NoteRepository;
use App\Template\TemplateRendererInterface;
use Joomla\Application\WebApplicationInterface;

final class ListNotesController extends AbstractController
{
    public function __construct(
        WebApplicationInterface $app,
        private readonly NoteRepository $notes,
        private readonly TemplateRendererInterface $renderer
    ) {
        parent::__construct($app);
    }

    public function execute(): bool
    {
        return $this->respond(
            $this->renderer->render('notes', [
                'notes' => $this->notes->findAll(),
                'user'  => $this->app->getSession()->get('user'),
                'token' => $this->app->getFormToken(),
            ])
        );
    }
}
```

## Reading one note

`src/Controller/ShowNoteController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\NoteRepository;
use App\Template\TemplateRendererInterface;
use Joomla\Application\WebApplicationInterface;

final class ShowNoteController extends AbstractController
{
    public function __construct(
        WebApplicationInterface $app,
        private readonly NoteRepository $notes,
        private readonly TemplateRendererInterface $renderer
    ) {
        parent::__construct($app);
    }

    public function execute(): bool
    {
        $note = $this->notes->find($this->input()->getUint('id'));

        if ($note === null) {
            return $this->respond('<h1>Note not found</h1>', 404);
        }

        return $this->respond($this->renderer->render('note', ['note' => $note]));
    }
}
```

`getUint()` is doing real work here: the route rule `['id' => '\d+']` already restricts the
segment, but the value reaches the controller through `Input`, where a query string `?id=…` can
override it — route variables are merged with `def()` and lose against an existing request
variable.

## Registering controllers in the container

`ContainerControllerResolver` asks the container first, so declare each controller with its
dependencies. Add to `WebApplicationProvider::register()`:

```php
$container->share(
    ListNotesController::class,
    static fn (Container $c) => new ListNotesController(
        $c->get(WebApplicationInterface::class),
        $c->get(NoteRepository::class),
        $c->get(TemplateRendererInterface::class)
    )
);

$container->share(
    ShowNoteController::class,
    static fn (Container $c) => new ShowNoteController(
        $c->get(WebApplicationInterface::class),
        $c->get(NoteRepository::class),
        $c->get(TemplateRendererInterface::class)
    )
);

// … the remaining controllers follow the same shape
```

You could rely on autowiring instead — `Container::buildObject()` resolves constructor arguments by
type hint. Two reasons not to here:

* Autowiring cannot resolve scalar constructor arguments at all, so any controller that needs a
  configuration value has to be registered explicitly anyway.
* `buildObject()` resolves the dependencies **once** and freezes them into the factory closure, so
  a service that is deliberately not shared still receives the same collaborators forever.

Explicit registration is a few more lines and no surprises.

## Trying it out

```bash
php -S localhost:8000 -t public
curl -i http://localhost:8000/
```

Until the repository exists this fails with a container error — that is next:
[Database and repositories](04-database-and-repositories.md).
