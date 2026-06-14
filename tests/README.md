# TimeTracker — Backend Test Suite

## Prerequisites

PHPUnit 10 is installed as a Composer dev dependency inside the Docker container:

```bash
sudo docker exec -w /var/www/html/custom_apps/timetracker nc34-test composer update
```

## Running the tests

```bash
sudo docker exec -w /var/www/html/custom_apps/timetracker nc34-test \
    php vendor/phpunit/phpunit/phpunit --testdox tests/Unit
```

All tests run without a database or a live Nextcloud instance.

## Test structure

```
tests/
├── bootstrap.php                          # vendor/autoload.php + CompatibleMapper alias
├── README.md
└── Unit/
    └── Controller/
        ├── FakeRequest.php                # Shared IRequest test double (see below)
        ├── ClientControllerTest.php       # create(), update()
        ├── GoalControllerTest.php         # index(), date helpers
        ├── TagControllerTest.php          # create(), update(), destroy()
        ├── TimelineControllerTest.php     # secondsToTime() helper
        └── TimerControllerTest.php        # start(), stop(), update(), destroy()
```

## FakeRequest — why it matters

`FakeRequest` is a shared `IRequest` test double that enforces Nextcloud 34's
JSON-body access semantics:

- `__get()` returns **null** for every property name.
- `__isset()` returns **false** for every property name.
- `getParam()` returns values from the constructor-supplied params array.

This mirrors the real `Request` class, where `__get()` for unknown keys reads
from `$items['parameters']` (built at construction from `$_GET`, `$_POST`
form-encoded data, and URL params only). JSON request bodies are **not** in
that array; they are parsed lazily by `getContent()`, which is only reachable
via `getParam()`.

Consequence: any controller that regresses to `$this->request->name` instead
of `$this->request->getParam('name')` will receive `null` from the stub.
In PHP 8.4, `trim(null)` throws a `TypeError`, making the test fail at write
time rather than silently passing while the bug surfaces in production.

## How to add new tests

1. Create `tests/Unit/Controller/YourControllerTest.php` in namespace
   `OCA\TimeTracker\Tests\Unit\Controller`.
2. Use `new FakeRequest(['paramKey' => 'value'])` as the `IRequest` argument.
3. Mock mapper dependencies with `$this->createMock(SomeMapper::class)`.
4. Call the controller method directly and assert on `$response->getData()`.
