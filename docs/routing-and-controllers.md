# Routing and controllers

`WebApplication` is the concrete application class. It takes a router and a controller resolver
and implements `doExecute()` as: parse the route, merge the route variables into the input, call
the controller.

## Constructing it

```php
use Joomla\Application\Controller\ContainerControllerResolver;
use Joomla\Application\WebApplication;
use Joomla\Router\Router;

$router = new Router();
$router->get('/', HomeController::class);
$router->get('/articles/:id', ArticleController::class, ['id' => '\d+']);
$router->post('/articles', CreateArticleController::class);

$app = new WebApplication(
    new ContainerControllerResolver($container),
    $router,
    $input,
    $config
);

$app->execute();
```

## What `doExecute()` does

```php
protected function doExecute(): void
{
    $route = $this->router->parseRoute($this->get('uri.route'), $this->input->getMethod());

    foreach ($route->getRouteVariables() as $key => $value) {
        $this->input->def($key, $value);
    }

    \call_user_func($this->controllerResolver->resolve($route));
}
```

Three consequences:

* Routing happens on the `uri.route` config key, which `loadSystemUris()` derived from the request.
  If your base URI detection is wrong, routing is wrong — set `site_uri` explicitly.
* Route variables are merged with `def()`, so they **do not overwrite** a request parameter of the
  same name. A query string `?id=99` wins over the route segment `/articles/5`. If that matters,
  read the route variables from the `ResolvedRoute` instead of from the input.
* The controller is called with **no arguments**. Everything it needs must come from its
  constructor.

## Route definition

The router accepts one method per verb, plus `all()`:

```php
$router->get('/articles', ListController::class);
$router->post('/articles', CreateController::class);
$router->put('/articles/:id', UpdateController::class, ['id' => '\d+']);
$router->delete('/articles/:id', DeleteController::class, ['id' => '\d+']);
$router->all('/health', HealthController::class);
```

Pattern syntax:

| Segment | Meaning |
|---|---|
| `articles` | Literal |
| `:id` | Named variable, matches `[^/]*`, or the rule you supply |
| `:` | Unnamed variable, not captured |
| `*rest` | Splat, captures everything remaining into `rest` |
| `*` | Splat, not captured |

> **Known routing limitation.** `Router::parseRoute()` throws `MethodNotAllowedException` at the
> *first* route whose pattern matches but whose method does not, without looking at later routes.
> Registering `get('/articles', …)` and `post('/articles', …)` therefore makes the POST route
> unreachable. Until that is fixed in the router package, register one route per pattern with
> `all()` and branch on `$app->getInput()->getMethod()` inside the controller.

## Controller resolvers

### `ControllerResolver`

Resolves, in order:

| Route controller value | Resolution |
|---|---|
| `[SomeClass::class, 'method']` | Instantiates `SomeClass`, returns `[$instance, 'method']` |
| `[$object, 'method']` | Returned as-is |
| An object with `__invoke()` | Returned as-is |
| A function name | Returned as-is |
| `SomeClass::class` (string) | Instantiates it and returns `[$instance, 'execute']` |

Anything else, or a class that cannot be instantiated, raises `InvalidArgumentException`.

`ControllerResolver::instantiateController()` calls `new $class()` — so a controller resolved this
way **must have a constructor without required arguments**. If it has any, you get an
`InvalidArgumentException` explaining exactly that.

### `ContainerControllerResolver`

Subclass that first asks a PSR-11 container:

```php
protected function instantiateController(string $class): object
{
    if ($this->container->has($class)) {
        return $this->container->get($class);
    }

    return parent::instantiateController($class);
}
```

This is the one you want in practice — it lets controllers declare their dependencies:

```php
$container->share(
    ArticleController::class,
    static fn (Container $c) => new ArticleController(
        $c->get(ArticleRepository::class),
        $c->get(TemplateRendererInterface::class),   // your own templating abstraction
        $c->get(WebApplicationInterface::class)
    )
);
```

> Controllers are resolved from a class name that ultimately derives from the request. Register the
> controllers you expect in the container and avoid building class names from request data — the
> resolver has no namespace allowlist and will happily instantiate any class that exists.

### Writing your own

```php
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Router\ResolvedRoute;

final class ActionResolver implements ControllerResolverInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function resolve(ResolvedRoute $route): callable
    {
        $controller = $route->getController();

        if (!\is_string($controller) || !$this->container->has($controller)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot resolve controller for URI `%s`', $route->getUri())
            );
        }

        return $this->container->get($controller);
    }
}
```

## Writing controllers

The resolver supports three shapes. Pick one and stay with it.

**Invokable** — the least ceremony:

```php
final class HomeController
{
    public function __construct(
        private WebApplicationInterface $app,
        private TemplateRendererInterface $templates
    ) {}

    public function __invoke(): void
    {
        $this->app->setBody($this->templates->render('home', ['title' => 'Home']));
    }
}
```

**`ControllerInterface`** — resolved to `execute()`, and the shape `joomla/controller` expects:

```php
use Joomla\Controller\ControllerInterface;

final class ArticleController implements ControllerInterface
{
    public function __construct(
        private ArticleRepository $articles,
        private WebApplicationInterface $app
    ) {}

    public function execute(): bool
    {
        $id      = $this->app->getInput()->getUint('id');
        $article = $this->articles->find($id);

        if ($article === null) {
            $this->app->setHeader('Status', '404', true);
            $this->app->setBody('Not found');

            return false;
        }

        $this->app->setBody($this->render($article));

        return true;
    }
}
```

**Array callable** — one class, several actions:

```php
$router->get('/articles', [ArticleController::class, 'index']);
$router->get('/articles/:id', [ArticleController::class, 'show'], ['id' => '\d+']);
```

Note that with an array callable the class is instantiated by `instantiateController()`, so with
`ContainerControllerResolver` it still comes from the container.
