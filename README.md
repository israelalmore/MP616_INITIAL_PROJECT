# Nurse API

REST API developed with **PHP** and **Symfony** for managing and consulting nurse information.

The API provides endpoints for creating, retrieving, updating and deleting nurses, looking up a nurse by username, and authenticating users. Data is stored in a **centralized PostgreSQL database hosted on Supabase**, shared by the whole team, and accessed through Doctrine ORM.

---

## Technologies

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- Supabase (centralized, remote database)
- REST API / JSON
- PostgreSQL (Supabase in development, PostgreSQL 16 in CI)
- PHPUnit
- GitHub Actions (CI)
- Postman
- Git / GitHub

---

## Project Structure

The main backend functionality is handled by the `NurseController` (`src/Controller/NurseController.php`).

It uses the `Nurse` entity and `NurseRepository` (see [Doctrine](#doctrine-entity-repository-and-migrations)). The endpoints are listed in [API Endpoints](#api-endpoints).

Other relevant folders:

- `src/Entity/`, `src/Repository/` — Doctrine entity and repository.
- `migrations/` — Doctrine migrations that define the database schema.
- `tests/` — PHPUnit tests (see [Running the tests](#running-the-tests)).
- `.github/workflows/tests.yml` — CI workflow (see [Continuous Integration](#continuous-integration-github-actions)).
- `data/` — SQL sample data (see [data/README.md](data/README.md)).
- [`postman.json`](postman.json) — importable Postman collection for the API.

> All endpoints except `GET /nurse` read from and write to the database configured in `DATABASE_URL`.

---

## API Endpoints

Base URL when running locally: `http://127.0.0.1:8000`. All responses are JSON and **no response includes the `password`**.

| Method | Route | Controller method | Description |
|--------|-------|-------------------|-------------|
| `GET` | `/nurse` | `index()` | Welcome message (no database access). |
| `POST` | `/nurse` | `create()` | Create a nurse. |
| `GET` | `/nurse/index` | `getAll()` | List all nurses ordered by `id`. |
| `GET` | `/nurse/id/{nurseId}` | `findById()` | Find a nurse by numeric ID (`nurseId` must be digits). |
| `GET` | `/nurse/name/{name}` | `findByName()` | Find a nurse by **username** (case-insensitive), not by display name. |
| `PUT` | `/nurse/{nurseId}` | `update()` | Update a nurse (`nurseId` must be digits). |
| `DELETE` | `/nurse/delete/{nurseId}` | `delete()` | Delete a nurse (`nurseId` must be digits). |
| `POST` | `/nurse/login` | `login()` | Check a username and password. |

### Status codes

| Endpoint | Success | Errors |
|----------|---------|--------|
| `GET /nurse` | `200` | — |
| `POST /nurse` | `201` with the created nurse | `400`: invalid JSON, invalid data (per-field `errors`), username already exists (case-insensitive), email already exists, unknown field. `500`: database failure |
| `GET /nurse/index` | `200` with an array of nurses | — |
| `GET /nurse/id/{nurseId}` | `200` | `404` `Enfermera no encontrada` |
| `GET /nurse/name/{name}` | `200` | `400` `El username no puede estar vacío` (blank after trimming); `404` `Enfermera no encontrada` |
| `PUT /nurse/{nurseId}` | `200` with the updated nurse | `400`: invalid JSON, body not a JSON object, invalid data, or username/email already in use. `404` `Enfermera no encontrada` |
| `DELETE /nurse/delete/{nurseId}` | `200` `Enfermera eliminada` | `404` `Enfermera no encontrada` |
| `POST /nurse/login` | `200` `Credenciales correctas` | `401` `Credenciales incorrectas` (missing/non-string fields, unknown user or wrong password) |

Error messages are currently a mix of Spanish and English, exactly as returned by the code.

Nurse object returned by the endpoints:

```json
{
  "id": 1,
  "name": "Ana Garcia",
  "username": "nurse1",
  "createdAt": "2026-10-20 09:00:00",
  "updatedAt": "2026-10-20 09:00:00",
  "profileImage": null,
  "email": "ana.garcia@example.com",
  "status": "active"
}
```

### `POST /nurse` — create

| Field | Required | Rules |
|-------|----------|-------|
| `name` | yes | string, not blank, max 100 characters |
| `username` | yes | string, not blank, max 64; unique, case-insensitive |
| `password` | yes | string, not blank, max 255 |
| `email` | yes | valid email, max 180; unique |
| `profileImage` | no | string, max 255 |
| `status` | no | string, not blank, max 20; defaults to `active` |

Any other field is rejected with `400`.

Request:

```json
{ "name": "Test Nurse", "username": "test.nurse", "password": "secret-demo", "email": "test.nurse@example.com" }
```

Response `201`:

```json
{
  "id": 1001,
  "name": "Test Nurse",
  "username": "test.nurse",
  "createdAt": "2026-11-05 10:00:00",
  "updatedAt": "2026-11-05 10:00:00",
  "profileImage": null,
  "email": "test.nurse@example.com",
  "status": "active"
}
```

Response `400` (validation, duplicate username or duplicate email; each key in `errors` is a field name):

```json
{
  "success": false,
  "message": "Datos de la enfermera inválidos",
  "errors": { "username": "El username ya existe" }
}
```

Other `400` cases: `El cuerpo de la petición debe ser un JSON válido` (invalid JSON), and `errors.nurse` = `El username o el email ya existen` when the database unique index rejects the insert. A database failure returns `500`:

```json
{ "success": false, "message": "No se ha podido registrar la enfermera" }
```

### `PUT /nurse/{nurseId}` — update

Requires `name` (max 100), `username` (max 64), `email` (valid, max 180) and `status` (max 20), all non-blank strings. Optional: `profileImage` (string up to 255 characters, or `null`) and `password` (non-blank string up to 255). Other fields are rejected. The required fields are always replaced (it is not a partial update).

Validation errors return `400`:

```json
{
  "success": false,
  "message": "Invalid nurse data",
  "errors": { "email": "This field must be a valid email address" }
}
```

Other `400` messages: `Invalid JSON body`, `The request body must be a JSON object` and `Username or email is already in use`.

### `DELETE /nurse/delete/{nurseId}`

Response `200`:

```json
{ "success": true, "message": "Enfermera eliminada" }
```

> The route is `/nurse/delete/{nurseId}`; `/nurse/{nurseId}` only accepts `PUT`.

### `POST /nurse/login`

Request `{"username": "nurse1", "password": "n1pass"}`. The username must match exactly (case-sensitive).

Response `200`: `{"success": true, "message": "Credenciales correctas"}`. Response `401`: `{"success": false, "message": "Credenciales incorrectas"}`.

> Known limitation: this is a demo project and passwords are stored and compared in **plain text** (`nurses.password`). Do not use real credentials.

---

## Requirements

Before running the project, make sure you have installed:

- PHP 8.2 or higher, with the `pdo_pgsql` extension enabled (CI also enables `intl` and `mbstring`)
- Composer
- Symfony CLI (recommended)
- Postman (optional, to try the API)
- Docker (optional, for a local PostgreSQL used by the tests; see `compose.yaml`)
- Access to the team's Supabase project (ask the project owner for an invitation)

Check the installed versions:

```bash
php --version
composer --version
symfony --version
php -m | grep pdo_pgsql
```

On Windows, if `pdo_pgsql` is not listed, uncomment `extension=pdo_pgsql` (and `extension=pgsql`) in your `php.ini` and restart the terminal.

---

## Installation

1. Clone the repository and enter the project folder:

   ```bash
   git clone https://github.com/israelalmore/MP616_INITIAL_PROJECT.git
   cd MP616_INITIAL_PROJECT
   ```

2. Install the PHP dependencies:

   ```bash
   composer install
   ```

3. Configure the database connection (see the next section).

4. Run the migrations and start the server:

   ```bash
   php bin/console doctrine:migrations:migrate
   symfony server:start
   ```

   Without the Symfony CLI you can use `php -S 127.0.0.1:8000 -t public`.

---

## Centralized Database (Supabase)

The project no longer uses a local database per developer. All team members connect to **one shared database hosted on Supabase**, so everyone works with the same schema and data.

Symfony connects to it through Doctrine, which reads the connection string from the `DATABASE_URL` environment variable (`config/packages/doctrine.yaml`).

### 1. Get the connection data

1. Log in to [Supabase](https://supabase.com) and open the team project.
2. Click **Connect** (top bar) and copy the connection string from **Session pooler** (recommended: it works on IPv4 networks).
3. Ask the project owner for the database password. **It is never shared through the repository, README, issues or chat history.**

The connection string has this format:

```
postgresql://postgres.<PROJECT_REF>:<DB_PASSWORD>@aws-0-<REGION>.pooler.supabase.com:5432/postgres
```

### 2. Configure `DATABASE_URL` in `.env.local`

The `.env` file is committed and must not contain real credentials. Keep your real `DATABASE_URL` **only** in **`.env.local`**, which is ignored by Git (see `.gitignore`) and overrides `.env`.

Create the file in the project root:

```bash
# Linux / macOS / Git Bash
touch .env.local
```

```powershell
# Windows PowerShell
New-Item .env.local -ItemType File
```

Add the following line, replacing the placeholders with your values:

```dotenv
DATABASE_URL="postgresql://postgres.<PROJECT_REF>:<DB_PASSWORD>@aws-0-<REGION>.pooler.supabase.com:5432/postgres?serverVersion=17&charset=utf8&sslmode=require"
```

| Placeholder | Where to find it |
|-------------|------------------|
| `<PROJECT_REF>` | Supabase → Project Settings → General → Project ID |
| `<REGION>` | Shown in the connection string copied from **Connect** |
| `<DB_PASSWORD>` | Provided privately by the project owner |
| `serverVersion` | Supabase → Project Settings → Infrastructure (Postgres version) |

Notes:

- If the password contains special characters (`@`, `:`, `/`, `#`, `?`, `%`…), they must be URL-encoded (for example `@` → `%40`).
- `sslmode=require` forces an encrypted connection to Supabase.
- **Never commit `.env.local`** and never put real credentials in `.env`, `.env.dev` or this README. Check with `git status` before every commit.

### 3. Run the Doctrine migrations

Migrations are stored in `migrations/` and create the schema in the shared database (the `nurses` table).

Check which migrations are pending:

```bash
php bin/console doctrine:migrations:status
```

Apply them:

```bash
php bin/console doctrine:migrations:migrate
```

Because the database is shared:

- A migration only needs to be executed **once** for the whole team. If `status` shows no pending migrations, someone already applied it.
- Do not run `doctrine:migrations:migrate prev`, `doctrine:schema:drop` or `doctrine:database:drop` without agreeing with the team — it affects everyone.
- Commit new migration files to Git so the rest of the team has them.

Optionally, load the sample data from [`data/nurse.sql`](data/README.md) (it inserts explicit IDs; see the sequence note there and in Troubleshooting).

Check the contents of each migration, and the duplicate pre-check required before `Version20261103120000`, in [Doctrine](#doctrine-entity-repository-and-migrations).

### 4. Verify the connection

Run any of these commands from the project root:

```bash
# Check that the migrations table can be read from Supabase
php bin/console doctrine:migrations:status

# Run a simple query against the remote database
php bin/console dbal:run-sql "SELECT 1"

# Check that the mapping and the database schema are in sync
php bin/console doctrine:schema:validate
```

The connection is working if `dbal:run-sql` returns a result and `doctrine:migrations:status` shows the database name and the executed migrations without errors.

You can also check in Supabase → **Table Editor** that the `nurses` and `doctrine_migration_versions` tables exist.

### Troubleshooting

| Error | Likely cause |
|-------|--------------|
| `could not find driver` | The `pdo_pgsql` extension is not enabled in `php.ini`. |
| `password authentication failed for user "postgres"` | Incorrect `DATABASE_URL` in `.env.local`: the pooler user is `postgres.<PROJECT_REF>` (not plain `postgres`), and special characters in the password must be URL-encoded. |
| `could not translate host name` / timeout | Wrong host or region, or the direct connection is being used on an IPv4-only network. Use the **Session pooler** string. |
| `SSL connection is required` | Add `sslmode=require` to `DATABASE_URL`. |
| Changes in `.env.local` are ignored | Clear the cache: `php bin/console cache:clear`. Check the file is named exactly `.env.local` and is in the project root. |
| `POST /nurse` returns `400` "El username o el email ya existen" with new data | The `nurses_id_seq` sequence is behind `MAX(id)` because `data/nurse.sql` inserts explicit IDs. Fix: `SELECT setval(pg_get_serial_sequence('nurses','id'), (SELECT MAX(id) FROM nurses));` |

---

## Doctrine: entity, repository and migrations

**Entity** `src/Entity/Nurse.php` (table `nurses`):

| Field | Type | Notes |
|-------|------|-------|
| `id` | integer | Primary key, generated by the database. |
| `name` | string(100) | |
| `username` | string(64) | Unique index `UNIQ_NURSE_USERNAME`. |
| `password` | string(255) | Stored in plain text (demo). |
| `email` | string(180) | Unique index `UNIQ_NURSE_EMAIL`. |
| `status` | string(20) | |
| `profileImage` | string(255), nullable | Column `profile_image`. |
| `createdAt`, `updatedAt` | datetime | Set by the `PrePersist` / `PreUpdate` lifecycle callbacks. |

**Repository** `src/Repository/NurseRepository.php`: `findAll()` (ordered by `id`), `findByUsername()` (case-insensitive), `save()` (persist + flush) and `validateCredentials()`.

**Migrations** (`migrations/`):

- `Version20261021161129` — creates the `nurses` and `messenger_messages` tables.
- `Version20261103120000` — adds the unique indexes on `nurses.username` and `nurses.email`.

> **Before running `Version20261103120000` on the shared Supabase database**, check that there are no duplicates, otherwise the migration fails. Both queries must return no rows:
>
> ```sql
> SELECT username, COUNT(*) FROM nurses GROUP BY username HAVING COUNT(*) > 1;
> SELECT email, COUNT(*) FROM nurses GROUP BY email HAVING COUNT(*) > 1;
> ```

---

## Running the tests

```bash
php bin/phpunit
```

- `tests/Controller/NurseControllerTest.php` and `tests/Repository/NurseRepositoryTest.php` use mocks and need no database.
- `tests/Controller/NurseCreateTest.php` runs against a **real database** inside a transaction that is rolled back. To avoid touching the shared Supabase database, it is **skipped unless the DB host is `127.0.0.1`, `localhost` or `database`**. With a Supabase `DATABASE_URL` you will see `Skipped: 14`; in CI they run.
- `phpunit.dist.xml` sets `failOnDeprecation`, `failOnNotice` and `failOnWarning`, so those also fail the run.

To run the database tests locally:

1. Start a local PostgreSQL (`docker compose up -d database`, defined in `compose.yaml`).
2. Create `.env.test.local` (ignored by Git) with a local `DATABASE_URL`, for example `postgresql://app:<LOCAL_PASSWORD>@127.0.0.1:5432/app?serverVersion=16&charset=utf8`.
3. In the `test` environment Doctrine appends `_test` to the database name (`app_test`). Create it and its schema with `php bin/console doctrine:database:create --env=test` and `php bin/console doctrine:schema:create --env=test`, then run `php bin/phpunit`.

---

## Continuous Integration (GitHub Actions)

Workflow `.github/workflows/tests.yml` (name **Backend tests**):

- **Triggers:** every `push` to any branch and every `pull_request` targeting `main`.
- **Environment:** `ubuntu-latest`, `postgres:16` service container, `APP_ENV=test`, PHP 8.2 (`pdo_pgsql`, `intl`, `mbstring`).
- **Steps:** checkout, `composer install`, create the test database and schema (`doctrine:database:create` / `doctrine:schema:create --env=test`), then `vendor/bin/phpunit --testdox`. Any failing test turns the job red.

To verify it ran: open the **Actions** tab of the GitHub repository and select the **Backend tests** run for your branch, or check the **Checks** section at the bottom of the pull request.

---

## Testing the API

Start the Symfony server, then import the root [`postman.json`](postman.json) file into Postman using **Import → File**. The collection's `base_url` variable defaults to `http://127.0.0.1:8000`; change it in the collection's **Variables** tab when using a different server.

The collection includes valid and invalid login requests, retrieving all nurses, searching by username and ID, create requests (success, missing fields), update requests (success, not found, missing field) and delete requests. Login defaults to the demo account `nurse1` / `n1pass` (development only). The search route is `GET /nurse/name/{name}`, which looks up a username (the default `search_name` is `nurse1`), not the nurse's display name.

**Run the collection in order.** "Create nurse - 201 Created" creates a temporary nurse with a unique username and email and stores its id in the `created_nurse_id` collection variable. The update and delete requests work on that nurse, and "Delete nurse - 200 OK" (`DELETE /nurse/delete/{nurseId}`) removes it, so a full run leaves no data behind. `nurse_id` (default `1`) is only used by read-only requests.

If "Create nurse - 201 Created" fails, the update and delete requests that depend on it fail too (they never fall back to an existing nurse). On Supabase this happens while the `nurses_id_seq` sequence is behind `MAX(id)` (see [Troubleshooting](#troubleshooting)).

You can also use `curl`:

```bash
curl http://127.0.0.1:8000/nurse/index
curl http://127.0.0.1:8000/nurse/id/1
curl http://127.0.0.1:8000/nurse/name/nurse1
curl -X POST http://127.0.0.1:8000/nurse -H "Content-Type: application/json" -d '{"name":"Test Nurse","username":"test.nurse","password":"secret-demo","email":"test.nurse@example.com"}'
curl -X POST http://127.0.0.1:8000/nurse/login -H "Content-Type: application/json" -d '{"username":"nurse1","password":"n1pass"}'
curl -X PUT http://127.0.0.1:8000/nurse/1001 -H "Content-Type: application/json" -d '{"name":"Test Nurse","username":"test.nurse","email":"test.nurse@example.com","status":"active"}'
curl -X DELETE http://127.0.0.1:8000/nurse/delete/1001
```

Replace `1001` with the ID returned by `POST /nurse`; do not update or delete real nurses on the shared database. See [API Endpoints](#api-endpoints) for status codes.

Demo accounts `nurse1` to `nurse1000` (from `data/nurse.sql`) use the password pattern `n<number>pass`. They are development data only, not production credentials.
