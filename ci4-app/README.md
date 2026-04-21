# CI4 App

## Requirements

- PHP 8.2+
- Composer
- MySQL/MariaDB (or another supported database)

## Install

1. Install dependencies:
   `composer install`
2. Copy `env` to `.env` and configure it:
   - `app.baseURL`
   - `CI_ENVIRONMENT`
   - `database.default.*` (hostname, database, username, password)
3. Create the database that matches your `.env` settings.
4. Run migrations:
   `php spark migrate`
5. Seed the database:
   `php spark db:seed AdminUserSeeder`

## Run (optional)

`php spark serve`
?
