# 4. Database and repositories

## The database provider

`src/Service/DatabaseProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\NoteRepository;
use App\Repository\UserRepository;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

final class DatabaseProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            DatabaseInterface::class,
            static function (Container $container): DatabaseInterface {
                $config = $container->get(Registry::class);

                $options = (array) $config->get('database')->toArray();
                $driver  = $options['driver'];
                unset($options['driver']);

                $db = (new DatabaseFactory())->getDriver($driver, $options);
                $db->connect();

                return $db;
            },
            true
        );

        $container->share(
            NoteRepository::class,
            static fn (Container $c) => new NoteRepository($c->get(DatabaseInterface::class)),
            true
        );

        $container->share(
            UserRepository::class,
            static fn (Container $c) => new UserRepository($c->get(DatabaseInterface::class)),
            true
        );
    }
}
```

## Schema

The framework has no migration system, so we keep the schema in one place and apply it from a
console command (written in [chapter 8](08-console-and-production.md)).

`src/Repository/schema.php` is not needed — put the statements in the command. For reference, the
two tables are:

```sql
CREATE TABLE notes_notes (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT    NOT NULL,
    body       TEXT    NOT NULL,
    created_at TEXT    NOT NULL
);

CREATE TABLE notes_users (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL
);
```

The `notes_` prefix comes from the `database.prefix` config key. In queries we write `#__` and the
driver substitutes it.

## The note repository

`src/Repository/NoteRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class NoteRepository
{
    public function __construct(private readonly DatabaseInterface $db) {}

    /** @return object[] */
    public function findAll(int $limit = 50): array
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName(['id', 'title', 'created_at']))
            ->from($this->db->quoteName('#__notes'))
            ->order($this->db->quoteName('created_at') . ' DESC');

        return $this->db->setQuery($query, 0, $limit)->loadObjectList();
    }

    public function find(int $id): ?object
    {
        $query = $this->db->createQuery()
            ->select('*')
            ->from($this->db->quoteName('#__notes'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $this->db->setQuery($query)->loadObject();
    }

    public function create(string $title, string $body): int
    {
        $createdAt = gmdate('Y-m-d H:i:s');

        $query = $this->db->createQuery()
            ->insert($this->db->quoteName('#__notes'))
            ->columns($this->db->quoteName(['title', 'body', 'created_at']))
            ->values(':title, :body, :created_at')
            ->bind(':title', $title)
            ->bind(':body', $body)
            ->bind(':created_at', $createdAt);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->insertid();
    }

    public function delete(int $id): void
    {
        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__notes'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }
}
```

## The rules that keep this safe

Two rules, applied without exception:

1. **Values are bound, never concatenated.** `->where('id = ' . $id)` is how SQL injection gets
   into a codebase. `->where('id = :id')->bind(':id', $id, ParameterType::INTEGER)` is the same
   amount of typing.
2. **Identifiers go through `quoteName()`.** Table and column names cannot be bound as parameters,
   so they must be quoted instead. If an identifier ever comes from outside the code — a sort
   column from a query string, say — check it against an allowlist first:

   ```php
   $allowed = ['title', 'created_at'];
   $sort    = $this->input()->getCmd('sort', 'created_at');

   if (!in_array($sort, $allowed, true)) {
       $sort = 'created_at';
   }

   $query->order($this->db->quoteName($sort) . ' DESC');
   ```

   `getCmd()` filters, but filtering is not validation: `getCmd()` on `id;DROP` returns `idDROP`,
   which is not an injection but is not a valid column either. The allowlist is what makes it
   correct.

`bind()` takes its value **by reference**, which matters in loops:

```php
// Wrong: every binding points at the same loop variable.
foreach ($ids as $id) {
    $query->bind(':id', $id, ParameterType::INTEGER);
}

// Right: bind once, execute per value, or use whereIn().
$query->whereIn($this->db->quoteName('id'), $ids, ParameterType::INTEGER);
```

## The user repository

`src/Repository/UserRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use Joomla\Database\DatabaseInterface;

final class UserRepository
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function findByUsername(string $username): ?object
    {
        $query = $this->db->createQuery()
            ->select('*')
            ->from($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('username') . ' = :username')
            ->bind(':username', $username);

        return $this->db->setQuery($query)->loadObject();
    }

    public function create(string $username, string $passwordHash): int
    {
        $query = $this->db->createQuery()
            ->insert($this->db->quoteName('#__users'))
            ->columns($this->db->quoteName(['username', 'password']))
            ->values(':username, :password')
            ->bind(':username', $username)
            ->bind(':password', $passwordHash);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->insertid();
    }
}
```

## Transactions

Wrap multi-statement writes:

```php
$this->db->transactionStart();

try {
    $noteId = $this->notes->create($title, $body);
    $this->tags->attach($noteId, $tagIds);

    $this->db->transactionCommit();
} catch (\Throwable $e) {
    $this->db->transactionRollback();

    throw $e;
}
```

## Why repositories rather than an ORM

The framework does not offer an object-relational mapper you should build on today, so the query
builder plus a thin repository per aggregate is the path of least resistance — and for an
application of this size it is also the smaller amount of code.

If you want an ORM, pick an externally maintained one (Doctrine, Cycle) and give it its own
connection, or keep the repositories and add mapping inside them. Either way, the two rules above
still apply: bind values, quote identifiers.

Next: [Views and templates](05-views-and-templates.md).
