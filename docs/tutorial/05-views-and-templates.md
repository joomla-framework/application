# 5. Views and templates

The framework does not ship a templating package you should build on today, so we use a template
engine directly. This tutorial uses [Plates](https://platesphp.com/) — plain PHP templates with a
small API — but nothing below is specific to it. Twig, Latte or your own `include` wrapper all fit
the same shape: a service that turns a template name plus data into a string.

## The template provider

`src/Service/TemplateProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use League\Plates\Engine;

final class TemplateProvider implements ServiceProviderInterface
{
    public function __construct(private readonly string $templatePath) {}

    public function register(Container $container): void
    {
        $container->share(
            Engine::class,
            fn (): Engine => new Engine($this->templatePath, 'php'),
            true
        );
    }
}
```

Register it in `bootstrap.php` alongside the other providers:

```php
$container->registerServiceProvider(new TemplateProvider(__DIR__ . '/templates'));
```

Controllers then take the engine as a constructor dependency:

```php
public function __construct(
    WebApplicationInterface $app,
    private readonly NoteRepository $notes,
    private readonly Engine $templates
) {
    parent::__construct($app);
}

public function execute(): bool
{
    return $this->respond($this->templates->render('notes', ['notes' => $this->notes->findAll()]));
}
```

## Keep the engine behind your own interface

Typing controllers against `League\Plates\Engine` couples them to Plates. One small interface keeps
that swappable, which is worth the six lines given how the framework's own templating packages have
come and gone:

```php
<?php

declare(strict_types=1);

namespace App\Template;

interface TemplateRendererInterface
{
    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Template;

use League\Plates\Engine;

final class PlatesRenderer implements TemplateRendererInterface
{
    public function __construct(private readonly Engine $engine) {}

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }
}
```

```php
$container->share(
    TemplateRendererInterface::class,
    static fn (Container $c) => new PlatesRenderer($c->get(Engine::class)),
    true
);
```

The rest of this tutorial injects `TemplateRendererInterface`.

## Escaping is your job

This is the most important thing in this chapter.

Plates does **not** escape automatically. Neither does raw PHP. Every value that reaches a template
has to be escaped at the point of output, and a template with a bare `<?= $var ?>` is a bug.

Plates provides `$this->e()`:

```php
<?= $this->e($note->title) ?>
```

If you switch to an auto-escaping engine such as Twig later, the direction of the risk inverts:
escaping becomes the default and you have to mark the few places that intentionally emit HTML.
Either way the rule is the same — know which mode your engine is in, and never guess.

Escaping is also context dependent. `$this->e()` produces HTML-safe text, which is correct inside
an element or a quoted attribute. It is **not** sufficient inside a `<script>` block, a `style`
attribute, or a URL. For those:

```php
<a href="<?= $this->e($base) ?>notes/<?= (int) $note->id ?>">…</a>

<script>
    const config = <?= json_encode(
        $config,
        JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>
```

The `JSON_HEX_*` flags stop a value containing `</script>` from closing the block.

## Templates

`templates/layout.php`:

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $this->e($title ?? 'Notes') ?></title>
    <meta name="csrf-token" content="<?= $this->e($token ?? '') ?>">
</head>
<body>
    <header>
        <a href="<?= $this->e($base) ?>">Notes</a>

        <?php if (!empty($user)): ?>
            <span>Signed in as <?= $this->e($user['username']) ?></span>
            <form method="post" action="<?= $this->e($base) ?>logout">
                <input type="hidden" name="<?= $this->e($token) ?>" value="1">
                <button type="submit">Sign out</button>
            </form>
        <?php else: ?>
            <a href="<?= $this->e($base) ?>login">Sign in</a>
        <?php endif; ?>
    </header>

    <main><?= $this->section('content') ?></main>
</body>
</html>
```

`templates/notes.php`:

```php
<?php $this->layout('layout', ['title' => 'All notes', 'user' => $user, 'token' => $token, 'base' => $base]) ?>

<h1>All notes</h1>

<?php if ($notes === []): ?>
    <p>No notes yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($notes as $note): ?>
            <li>
                <a href="<?= $this->e($base) ?>notes/<?= (int) $note->id ?>">
                    <?= $this->e($note->title) ?>
                </a>
                <time><?= $this->e($note->created_at) ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($user)): ?>
    <h2>New note</h2>
    <form method="post" action="<?= $this->e($base) ?>notes/create">
        <input type="hidden" name="<?= $this->e($token) ?>" value="1">
        <label>Title <input type="text" name="title" required maxlength="200"></label>
        <label>Body <textarea name="body" required></textarea></label>
        <button type="submit">Save</button>
    </form>
<?php endif; ?>
```

`templates/note.php`:

```php
<?php $this->layout('layout', ['title' => $note->title, 'base' => $base]) ?>

<article>
    <h1><?= $this->e($note->title) ?></h1>
    <time><?= $this->e($note->created_at) ?></time>
    <div><?= nl2br($this->e($note->body)) ?></div>
</article>
```

Note the order in `nl2br($this->e(...))`: escape first, then convert newlines. The other way round
would escape the `<br>` tags that `nl2br()` just produced.

## Passing shared data

Every template needs `$base`, and most need `$token` and `$user`. Rather than repeating that in
each controller, add it once. With Plates that is `addData()`:

```php
$container->share(
    Engine::class,
    function (Container $container): Engine {
        $engine = new Engine($this->templatePath, 'php');

        $app = $container->get(WebApplicationInterface::class);

        $engine->addData([
            'base'  => $app->get('uri.base.full'),
            'token' => $app->getFormToken(),
            'user'  => $app->getSession()->get('user'),
        ]);

        return $engine;
    },
    true
);
```

Data passed to `render()` wins over shared data for the same key.

If you wrap the engine behind `TemplateRendererInterface`, put the shared data in the wrapper
instead so the interface stays engine-agnostic:

```php
public function render(string $template, array $data = []): string
{
    return $this->engine->render($template, array_replace($this->shared, $data));
}
```

Use `array_replace()` rather than `array_merge()` — `array_merge()` renumbers numeric keys instead
of overwriting them.

## JSON responses

For an API endpoint skip templating entirely:

```php
protected function json(array $data, int $status = 200): bool
{
    $this->app->mimeType = 'application/json';
    $this->app->setHeader('Status', (string) $status, true);
    $this->app->setBody(
        json_encode($data, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    );

    return $status < 400;
}
```

`JSON_THROW_ON_ERROR` turns an encoding failure into an exception instead of an empty body.

Next: [Session, authentication and CSRF](06-session-authentication-csrf.md).
