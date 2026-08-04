# Puko Console

**puko/console** is the CLI scaffolding tool for the **Puko Framework**. It automates database setup, model/controller generation, routing, authentication scaffolding, UI generation, and localization scanning.

- **License:** MIT
- **Requires:** PHP >= 7.0 or >= 8.0
- **Current version:** 0.4.0
- **Autoload namespace:** `pukoconsole\` -> `src/`
- **Entry point:** `puko` (a thin PHP script that boots `pukoconsole\Console` and runs `Execute()`)

> This is a **development-time tool**. It generates and modifies files inside an existing Puko Framework project (the "project root"). It is not a runtime library for application code.

---

## Installation

```bash
composer require puko/console
```

The console is invoked from the **project root** (the directory containing your Puko project's `config/`, `controller/`, `model/`, `plugins/`, `assets/`, `tests/` folders).

---

## Usage

```bash
php puko <command> [directive] [action] [attribute]
```

- `php puko help` — prints the built-in help menu
- `php puko version` — prints the console version
- `php puko <unknown>` — prints "Command not found!"

The console is **interactive**: many commands prompt for input on `STDIN`. Pressing **Enter** with an empty value accepts the previously selected/default value.

---

## Command Reference

### `setup` — Installation & first-time project setup

Initializes database connections, security config, and scaffolds project classes.

| Directive | Action | Attribute | Effect |
|---|---|---|---|
| `db` | — | `<schema>` | Interactive DB setup; generates models, contracts and test files for every table in the schema. |
| `secure` | — | — | Interactive AES-256 encryption config → writes `config/encryption.php`. |
| `auth` | `<name>` | — | Generates an auth plugin → `plugins/auth/<name>.php`. |
| `controller` | `view` | `<name>` | Generates a view controller → `plugins/controller/<name>.php`. |
| `controller` | `service` | `<name>` | Generates a service controller → `plugins/controller/<name>.php`. |
| `model` | `add` | `<name>` `<schema>` | Interactive model builder; generates model + model/controller test files. |
| `model` | `update` | `<name>` `<schema>` | Reserved (currently a no-op). |
| `model` | `remove` | `<name>` `<schema>` | Reserved (currently a no-op). |

#### `setup db` interactive prompts

Prompted in order (Enter accepts the default shown in parentheses):

1. Database Type — `mysql`, `oracle`, `sqlsrv`, `mongo`
2. Hostname
3. Port
4. Schema Name
5. Database Name
6. Username
7. Password
8. Driver (`odbc` or `sqlsrv` — used for SQL Server connections)
9. Ignored Table Prefix (default `_`; tables starting with it are skipped)
10. Hide Column list (default `created,modified,cuid,muid,dflag,password`)
11. "Add another database connection schema? (y/n)" — repeat the flow for extra schemas

For each table, `setup db` generates:

- `plugins/model/<schema>/<Table>.php` — the model class
- `model/<schema>/<Table>Contracts.php` — the data-access contracts
- `tests/unit/model/<schema>/<Table>ModelTest.php`
- `tests/unit/controller/<schema>/<Table>ControllerTest.php`

Connection blocks are appended to `config/database.php`. The config file structure per schema:

```php
$db['<schema>'] = [
    'dbType' => 'mysql',            // mysql | oracle | sqlsrv | mongo
    'host'   => 'localhost',
    'user'   => 'root',
    'pass'   => '',
    'dbName' => 'dbname',
    'port'   => '3306',
    'driver' => 'pdo',              // odbc | sqlsrv for SQL Server
    'ignoreTableWithPrefix' => '_',
    'hideColumns' => ['created', 'modified', 'cuid', 'muid', 'dflag', 'password'],
];
```

**Supported for `setup db`:** `mysql` (fully supported), `sqlsrv` (supported). `oracle` and `mongo` are stubbed (error "not yet supported").

---

### `refresh db` — Reapply database config without rewriting the file

```bash
php puko refresh db <schema>
```

Same interactive prompts as `setup db`, but **does not** rewrite `config/database.php` — useful when you only want to regenerate models/contracts/tests from external DB changes.

---

### `routes` — Routing and controller scaffolding

Reads/writes `config/routes.php`.

| Directive | Action | Attribute | Effect |
|---|---|---|---|
| `list` | — | — | Print all registered routes (`path => controller@function [ACCEPT]`). |
| `dir` | — | — | Print route paths only, with accepted methods. |
| `resort` | — | — | Re-sort routes alphabetically and rewrite `config/routes.php`. |
| `error` | — | — | Set the error-handler controller. |
| `lost` | — | — | Set the not-found controller. |
| `service` | `add` | `<url>` | Register a service route + scaffold controller. |
| `service` | `update` | `<url>` | Update an existing service route. |
| `service` | `crud` | `<schema>/<table>` | Generate a full CRUD service + 7 routes (see below). |
| `view` | `add` | `<url>` | Register a view route + scaffold controller + HTML/JS assets. |
| `view` | `update` | `<url>` | Update an existing view route. |
| `console` | `add` | `<url>` | Register a console route (accepts `GET`). |
| `console` | `update` | `<url>` | Update an existing console route. |
| `socket` | `add` | `<url>` | Register a socket route + scaffold a WebSocket controller (Ratchet). |
| `remove` | — | — | **Intentionally disabled** — delete routes manually. |

**Interactive prompts** (for add/update/error/lost):

1. Controller (use `\` for sub-directories, e.g. `entities\reports`)
2. Function name
3. Accept? `GET,POST,PUT,PATCH,DELETE` (comma-separated; console/socket routes default to `GET`)

**URL parameter syntax:** use `{?}` in the URL for a parameter, e.g. `posts/{?}/update`. The scaffolded controller method receives `$id#` args (e.g. `$id1 = ''`).

`view` routes additionally generate:
- `assets/html/en/<controller>/<function>.html`
- `assets/html/id/<controller>/<function>.html`
- `assets/scripts/<controller>/<function>.js`

#### `routes service crud <schema>/<table>`

Prerequisite: model exists at `plugins/model/<schema>/<table>.php` (run `setup db` first). Generates `controller/<schema>/<table>.php` with methods `create, update, delete, explore, search, table, read`, and registers these routes:

| Route | Method |
|---|---|
| `<table>/create` | POST |
| `<table>/{?}/update` | PUT |
| `<table>/{?}/delete` | DELETE |
| `<table>/explore` | POST |
| `<table>/table` | POST |
| `<table>/search` | POST |
| `<table>/{?}` | GET |

Columns listed in the schema's `hideColumns` are excluded from validation/vars/responses. After generation, rebuild language with `php puko language controller/<schema>`.

---

### `generate` — Wizards

| Directive | Effect |
|---|---|
| `db` | Interactive "generate" pass: reads models from `plugins/model/<schema>` and creates the database/tables from their doc-comment metadata (MySQL only). |
| `ui` | Interactive DataTables generator (see below). |

#### `generate ui` (DataTables)

Prompts for **Database Schema (primary)** (default `primary`) and **Table**, then reads the table schema from the DB and generates:

- `controller/ui/<schema>.php` (view controller)
- `assets/html/en/ui/<schema>/<table>.html` and `assets/html/id/...`
- `assets/scripts/ui/<schema>/<table>.js` (DataTables logic wired to the CRUD API endpoints)
- registers route `<schema>/<table>` (GET)

Prerequisite: a working backend CRUD API (e.g. from `routes service crud`).

---

### `language` — Localization scanner

```bash
php puko language <directory path>
```

Recursively scans PHP files under `<directory>` for `say(...)` directives, appends missing keys to `assets/master/id.master.json`, and writes it back (pretty-printed). Existing keys are preserved.

---

### `serve` — Local dev server

```bash
php puko serve [port]
```

Starts the PHP built-in server: `php -S localhost:<port> routes.php` (default port `4000`). Ctrl+C to stop.

---

### `cli` — Console-mode execution

```bash
php puko cli <router path>
```

Runs the router's console controller without a web server: `php cli <router path>`.

---

### `tests` — Run unit tests (beta)

```bash
php puko tests <directive>
```

Runs `vendor\bin\phpunit` in the project root.

---

### `element` — View elements

| Directive | Attribute | Effect |
|---|---|---|
| `add` | `<element name>` | Generates a scaffolded element plugin (PHP + HTML + JS + CSS) in `plugins/elements/<lowercase-name>/`. |
| `download` | `<element name>` (*beta) | Downloads an element from the GitHub elements repo (`config/init.php` `repo` key) into `plugins/elements/`. |

---

### `docs` — Automated docs

Currently a **stub** (no-op; "Command not supported!").

---

## Not implemented / reserved

| Feature | Status |
|---|---|
| `migrate` command | Referenced in help; migration code lives at `src/migration/Table.php` (all methods empty) — not wired in `Console.php`. |
| `setup db` for `oracle` / `mongo` | Error "not yet supported". |
| `generate db` for non-MySQL | No-op. |
| `setup model update` / `remove` | Reserved no-ops. |
| `routes remove` | Deliberately disabled. |
| `docs` | Stub. |
| `tests` | Beta. |

---

## Generated file map

| Command | Files written |
|---|---|
| `setup db <schema>` | `config/database.php`, `plugins/model/<schema>/*.php`, `model/<schema>/*Contracts.php`, `tests/unit/{model,controller}/<schema>/*.php` |
| `setup secure` | `config/encryption.php` |
| `setup auth <name>` | `plugins/auth/<name>.php` |
| `setup controller <view\|service> <name>` | `plugins/controller/<name>.php` |
| `setup model add <name> <schema>` | `plugins/model/<schema>/<name>.php`, `tests/unit/model/<schema>/<name>ModelTest.php`, `tests/unit/controller/<schema>/<name>ControllerTest.php` |
| `routes ... add` | `config/routes.php`, `controller/**/*.php`, `assets/html/{en,id}/...`, `assets/scripts/...` |
| `routes service crud` | `controller/<schema>/<table>.php`, `config/routes.php` |
| `generate ui` | `controller/ui/<schema>.php`, `assets/html/{en,id}/ui/<schema>/<table>.html`, `assets/scripts/ui/<schema>/<table>.js`, `config/routes.php` |
| `language <dir>` | `assets/master/id.master.json` |
| `element add <name>` | `plugins/elements/<name>/{name,<name>.php,<name>.html,<name>.js,<name>.css}` |

---

## Project structure

```
puko                      CLI entry point (boots Console::Execute)
src/
  Console.php             Command dispatcher (setup, routes, generate, etc.)
  Auth.php                setup auth
  Cli.php                 cli command
  Controller.php          setup controller
  Database.php            setup/refresh/generate db
  Docs.php                docs (stub)
  Elements.php            element add/download
  Language.php            say() scanner
  Models.php              setup model
  Routes.php              routes command + CRUD generator
  Secure.php              setup secure
  Serve.php               serve command
  Tests.php               tests command
  Ui.php                  generate ui (DataTables)
  config/
    help.md               Built-in help text
    init.php              Version + elements repo URL
  migration/Table.php     Migration placeholders (empty)
  template/               File templates used by generators
    assets/  config/  controller/  model/  plugins/
  util/
    Colors.php            ANSI color output
    Commons.php           var_export54, download (curl)
    Echos.php             Prints()
    Input.php             Read() interactive prompt
    TestingToolkit.php    SendRequest, WriteDocs helpers
```
