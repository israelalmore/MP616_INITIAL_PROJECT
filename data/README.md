# Nurse sample data

`nurse.sql` contains 1,000 sample rows for the single `nurses` table. Accounts `nurse1` through `nurse1000` use plain-text demo passwords following the pattern `n<number>pass` (for example, `n8pass`). These are development-only credentials and must not be reused elsewhere.

Apply the database migrations before importing the rows:

```sh
php bin/console doctrine:migrations:migrate
```

The rows are inserted with **explicit `id` values**, so the `nurses_id_seq` sequence is not advanced. After importing, align it; otherwise `POST /nurse` may fail with a duplicate-key error reported as `400` ("El username o el email ya existen"):

```sql
SELECT setval(pg_get_serial_sequence('nurses','id'), (SELECT MAX(id) FROM nurses));
```

The nurse API reads and writes records through the Doctrine-managed `NurseRepository` / `Nurse` entity (see the main [README](../README.md)).
