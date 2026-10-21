# Nurse sample data

`nurse.sql` contains 1,000 sample rows for the single `nurses` table. For local login testing, accounts `nurse1` through `nurse1000` use plain-text demo passwords `n1pass` through `n1000pass`. Apply the database migrations before importing the rows:

```sh
php bin/console doctrine:migrations:migrate
```

For local API login testing, accounts `nurse1` through `nurse2000` use short demo passwords following the pattern `n<number>pass` (for example, `n8pass`). The current demo login repository is configured with these same accounts. These sample passwords are not production credentials and should not be reused outside local development.

The importer accepts files up to 5 MiB and imports at most 10,000 credentials per file. An import is transactional: if any record is invalid or already exists, no records from that file are inserted. Passwords in the JSON are demo login data; credential imports do not create or update login accounts.

The nurse API reads records through the Doctrine-managed nurse repository. `GET /index`, `GET /nurse/name/{name}`, and a successful `POST /login` response include a `credentials` array with each nurse's license number, certification, issuing body, and expiration date. The name-search endpoint supports partial, case-insensitive matches and returns an empty JSON list when no nurses match.
