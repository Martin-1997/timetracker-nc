# AGENTS.md — Contributor guide for AI agents

This file describes the architecture, conventions, gotchas, and workflows an AI
agent needs to work effectively on this codebase without repeating known
mistakes.

---

## Project overview

**Time Tracker** is a Nextcloud 34 app (PHP 8.4, Vue 3).  It lets users track
time against tasks, projects, clients, tags, and goals.

Installed path inside the container: `/var/www/html/custom_apps/timetracker`

The repository is mounted as a Docker volume, so edits on the host are
immediately live inside the container — no deploy step needed for PHP changes.

---

## Development environment

All runtime commands go through the Docker container:

```bash
# PHP / Nextcloud
sudo docker exec -w /var/www/html/custom_apps/timetracker nc34-test <cmd>

# Frontend build (run on the HOST, not inside the container — Node inside
# the container OOMs with the available RAM)
cd js && NODE_OPTIONS='--max-old-space-size=1024' npx webpack --config webpack.config.js
```

The Nextcloud web UI is at **http://localhost:8080** (admin / admin_test_pw).

**Do not** delete the container, volumes, or images without explicit
confirmation.  All `docker` commands must use `sudo`.

---

## Architecture

### Dual route tree

Two parallel route trees coexist in `appinfo/routes.php`:

| Prefix | Controller | Purpose |
|--------|-----------|---------|
| `/ajax/*` | `AjaxController` | Legacy routes kept for backward compatibility |
| `/api/v1/*` | Domain controllers | REST API documented in `api/openapi.yaml` |

The frontend (`js/src/api/index.js`) calls `/api/v1/*` exclusively.
`AjaxController` is kept alive so any external code that still calls `/ajax/*`
continues to work.

### Domain controllers

Each domain controller lives in `lib/Controller/` and extends
`BaseApiController`:

| Controller | Prefix | Resource |
|-----------|--------|---------|
| `TimerController` | `/api/v1/timer`, `/api/v1/work-intervals` | Timer start/stop, work intervals |
| `ClientController` | `/api/v1/clients` | Clients |
| `ProjectController` | `/api/v1/projects` | Projects |
| `TagController` | `/api/v1/tags` | Tags |
| `GoalController` | `/api/v1/goals` | Goals |
| `ReportController` | `/api/v1/report` | Reports |
| `TimelineController` | `/api/v1/timelines` | Timelines |

### OpenAPI spec

`api/openapi.yaml` is the authoritative description of all `/api/v1/*`
endpoints.  Keep it in sync whenever routes or response shapes change.

---

## Critical PHP gotcha — IRequest parameter access

**Never** read POST/PUT body parameters via `$this->request->propertyName`.

Nextcloud 34's `Request::__get()` for unknown property names reads from
`$items['parameters']`, which is built at construction time from
`$_GET + $_POST(form-encoded) + urlParams`.  JSON request bodies are **not**
included.  JSON bodies are parsed lazily by `getContent()`, which is only
reachable via `getParam()`.

| Access pattern | Sees JSON body? |
|---------------|----------------|
| `$this->request->name` | **No** — returns null |
| `isset($this->request->name)` | **No** — returns false |
| `$this->request->getParam('name')` | **Yes** |
| `$this->request->getParam('name', '')` | **Yes** (with default) |

The frontend sends all POST/PUT bodies as `application/json` via
`@nextcloud/axios`.  Always use `getParam()` in write handlers.

GET query-string parameters (e.g. `$this->request->from` in `index()`) do work
via property access because they are in `$_GET`, but `getParam()` also works
and is safer to use everywhere.

---

## Other known gotchas

### vendor/autoload.php must NOT be required in Application.php

`vendor/nextcloud/ocp` is a stub package used only for unit-test autoloading.
If `require_once __DIR__ . '/../../vendor/autoload.php'` is added to
`Application.php`, the stub's autoloader registers itself over Nextcloud's real
class loader and every page returns HTTP 500.

### PHP 8.4 strict built-ins

`trim()`, `strlen()`, `str_replace()` etc. throw `TypeError` when passed
`null` in PHP 8.4.  Always cast or provide a default before passing a
potentially-null request param to these functions:

```php
$name = (string)($this->request->getParam('name', ''));
```

### intdiv() for integer arithmetic

`floor($x / $y)` returns `float` in PHP 8.4.  Use `intdiv($x, $y)` for
integer division and `$x % $y` for modulo when you need integers.

### ReportItemMapper empty WHERE clause

`implode(' AND ', [])` produces an empty string.  The WHERE clause must be
conditional: `$filters ? 'WHERE ' . implode(' AND ', $filters) : ''`.

---

## Running the tests

Unit tests run inside the container without a database:

```bash
sudo docker exec -w /var/www/html/custom_apps/timetracker nc34-test \
    php vendor/phpunit/phpunit/phpunit --testdox tests/Unit
```

Expected: **44 tests, 123 assertions, 0 failures**.

### FakeRequest — the IRequest test double

`tests/Unit/Controller/FakeRequest.php` is a shared stub where:
- `__get()` returns `null` for every key
- `__isset()` returns `false` for every key
- `getParam()` returns values from the constructor array

This mirrors the real Nextcloud behaviour and means any controller that regresses
to `$this->request->propertyName` for body params will receive `null`, causing
a `TypeError` in PHP 8.4 and a test failure.  Use `FakeRequest` in all
controller unit tests.

---

## Frontend build

Built with Webpack 5.  Source: `js/src/`.  Output: `js/dist/main.js`.

```bash
# From the repository root (host, not container)
cd js && NODE_OPTIONS='--max-old-space-size=1024' npx webpack --config webpack.config.js
```

The container's Node environment OOMs during the build (insufficient RAM + no
swap).  Run webpack on the host with the heap cap above.

The API client is `js/src/api/index.js`.  It uses `@nextcloud/axios`, which
automatically attaches the CSRF `requesttoken` header to every non-GET request.

---

## File map

```
appinfo/
  info.xml              App metadata (id, version, dependencies)
  routes.php            All routes — dual tree: /ajax/* and /api/v1/*

api/
  openapi.yaml          OpenAPI 3.1 spec for /api/v1/* endpoints

lib/
  AppInfo/
    Application.php     DI container bootstrap (mapper registrations)
  AppFramework/Db/
    OldNextcloudMapper.php   Compatibility shim for Mapper base class
  Controller/
    BaseApiController.php    Shared base (userId injection, isThisAdminUser)
    AjaxController.php       Legacy /ajax/* handler — do not modify
    *Controller.php          Domain controllers for /api/v1/*
  Db/
    *.php                    Entity + Mapper pairs (one per DB table)

js/
  src/
    api/index.js         Frontend API client (all calls go to /api/v1/*)
    views/               Vue SPA views
    main.js              Entry point
  webpack.config.js
  vitest.config.js

tests/
  bootstrap.php          Loads vendor/autoload.php + CompatibleMapper alias
  Unit/Controller/
    FakeRequest.php      Shared IRequest test double
    *Test.php            Per-controller unit tests
```

---

## Constraints

- **Do not** `git push`, open PRs, or publish anything.
- **Do not** delete Docker containers, volumes, or images without explicit
  confirmation.
- **All** `docker` commands require `sudo`.
