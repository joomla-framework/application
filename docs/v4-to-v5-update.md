# Updating from v4 to v5

Release 5.0.0 removes API that was deprecated in 4.x and adds native types where the 4.x API relied
on doc blocks. Most changes can be prepared while still on 4.x: fix the deprecation notices first,
then upgrade.

## At a glance

| | v4 (4.x) | v5 (5.0.0) |
|---|---|---|
| PHP | `^8.3.0` | unchanged |
| Controller class without `ControllerInterface` in `ControllerResolver` | deprecated, works | **throws** |

## Controller classes must implement `ControllerInterface`

`ControllerResolver` (and `ContainerControllerResolver`) resolve a route controller given as a class
name to `[$instance, 'execute']`. In 4.x a class that does not implement
`Joomla\Controller\ControllerInterface` was still resolved, with a deprecation notice. In 5.0 it
raises an `InvalidArgumentException`.

Either implement the interface:

```php
use Joomla\Controller\ControllerInterface;

final class ArticleController implements ControllerInterface
{
    // …
}
```

or register the route with an explicit callable, which works for any class:

```php
$router->get('/articles', [ArticleController::class, 'execute']);
```

## Dependency changes

None so far.
