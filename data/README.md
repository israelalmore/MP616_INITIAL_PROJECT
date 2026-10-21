# Nurse sample data

`nurse.sql` contains 1,000 sample rows for the single `nurses` table. For local login testing, accounts `nurse1` through `nurse1000` use plain-text demo passwords `n1pass` through `n1000pass`. Apply the database migrations before importing the rows:

```sh
php bin/console doctrine:migrations:migrate
```

Import the sample data with your MySQL/MariaDB client after the migration. The included accounts use demo passwords and are intended only for local development.
