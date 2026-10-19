# Nurse credential JSON import

The version 1 format is defined by [nurse_credentials.schema.json](./nurse_credentials.schema.json). Each nurse has a `user`, a demo `password`, and one or more credential records containing a license number, certification, issuing body, and ISO 8601 `YYYY-MM-DD` issue and expiration dates. Expired credentials and duplicate nurse/license/certification combinations are rejected.

To store credentials in the database for use by other application features, apply migrations and run the importer from the project root:

```sh
php bin/console doctrine:migrations:migrate
php bin/console app:nurse-credentials:import data/nurse_credentials.json
```

For local API login testing, accounts `nurse1` through `nurse2000` use short demo passwords following the pattern `n<number>pass` (for example, `n8pass`). The current demo login repository is configured with these same accounts. These sample passwords are not production credentials and should not be reused outside local development.

The importer accepts files up to 5 MiB and imports at most 10,000 credentials per file. An import is transactional: if any record is invalid or already exists, no records from that file are inserted. Passwords in the JSON are demo login data; credential imports do not create or update login accounts.

The nurse API reads records through the Doctrine-managed nurse repository. `GET /index`, `GET /nurse/name/{name}`, and a successful `POST /login` response include a `credentials` array with each nurse's license number, certification, issuing body, and expiration date. The name-search endpoint supports partial, case-insensitive matches and returns an empty JSON list when no nurses match.
