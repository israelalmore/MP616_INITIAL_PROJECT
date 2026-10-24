# Nurse API

REST API developed with **PHP** and **Symfony** for managing and consulting nurse information.

The API provides endpoints for retrieving nurses, searching nurses by name, and authenticating users. Nurse credentials can also be imported into a **centralized database hosted on Supabase**, shared by the whole team.

---

## Technologies

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM + Doctrine Migrations
- Supabase (centralized, remote database)
- REST API / JSON
- Postman
- Git / GitHub

---

## Project Structure

The main backend functionality is handled by the `NurseController` (`src/Controller/NurseController.php`).

| Method | Route | Description |
|--------|-------|-------------|
| `getAll()` | `GET /nurse/index` | Retrieve all nurses. |
| `findByName(name)` | `GET /nurse/name/{name}` | Find a nurse by name. |
| `login(user, password)` | `POST /nurse/login` | Authenticate a user. |

Other relevant folders:

- `src/Command/` — `app:nurse-credentials:import` console command.
- `migrations/` — Doctrine migrations that define the database schema.
- `data/` — Nurse credentials JSON and its schema (see [data/README.md](data/README.md)).
- `postman/` — Postman collection to test the API.

> The API endpoints read from `data/nurse_credentials.json` and do **not** require the database. The database is used to store credentials imported with the console command.

---

## Requirements

Before running the project, make sure you have installed:

- PHP 8.2 or higher, with the `pdo_pgsql` extension enabled
- Composer
- Symfony CLI (recommended)
- Postman
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

The `.env` file is committed and contains only placeholder values. Your real credentials go in **`.env.local`**, which is ignored by Git (see `.gitignore`) and overrides `.env`.

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

Migrations are stored in `migrations/` and create the schema in the shared database (for example, the `nurse_credentials` table).

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

Optionally, import the nurse credentials into the database:

```bash
php bin/console app:nurse-credentials:import data/nurse_credentials.json
```

The import is transactional; if any record already exists, nothing is inserted. See [data/README.md](data/README.md) for details.

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

You can also check in Supabase → **Table Editor** that the `nurse_credentials` and `doctrine_migration_versions` tables exist.

### Troubleshooting

| Error | Likely cause |
|-------|--------------|
| `could not find driver` | The `pdo_pgsql` extension is not enabled in `php.ini`. |
| `password authentication failed` | Wrong password, or user is not in the `postgres.<PROJECT_REF>` format. |
| `could not translate host name` / timeout | Wrong host or region, or the direct connection is being used on an IPv4-only network. Use the **Session pooler** string. |
| `SSL connection is required` | Add `sslmode=require` to `DATABASE_URL`. |
| Changes in `.env.local` are ignored | Clear the cache: `php bin/console cache:clear`. Check the file is named exactly `.env.local` and is in the project root. |

---

## Testing the API

With the server running, import the collection from the `postman/` folder in Postman, or use `curl`:

```bash
curl http://127.0.0.1:8000/nurse/index
curl http://127.0.0.1:8000/nurse/name/nurse1
curl -X POST http://127.0.0.1:8000/nurse/login -H "Content-Type: application/json" -d '{"user":"nurse1","password":"n1pass"}'
```

Demo accounts `nurse1` to `nurse2000` use the password pattern `n<number>pass`. They are local development data only, not production credentials.
