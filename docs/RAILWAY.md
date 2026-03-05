# Railway Deployment

## PHP requirements

- **PHP:** 8.2 (locked in `composer.json` platform so the lock file is compatible).
- **Extensions:** `intl` and `zip` are required (Filament/OpenSpout). **You must set this in Railway:**
  - **Variable:** `RAILPACK_PHP_EXTENSIONS`
  - **Value:** `intl,zip`
  - Without this, the build will fail with "ext-intl is missing" / "ext-zip is missing".

## Build

- **Build command:** `composer install --no-dev --optimize-autoloader`
- Do not run `php artisan migrate` during build (PostgreSQL may not be available).

## Start

- **Start command:** `php artisan serve --host=0.0.0.0 --port=$PORT`
- Railway sets `PORT`; binding to `0.0.0.0` is required.

## Migrations

Run after deployment (when DB is available):

```bash
php artisan migrate --force
```

Use Railway's shell or a one-off run. Do not run migrations in the build phase.

## Required environment variables

| Variable                   | Required | Description |
|----------------------------|----------|-------------|
| `RAILPACK_PHP_EXTENSIONS`  | **Yes**  | Set to `intl,zip` so the build installs these PHP extensions. |
| `APP_KEY`                  | Yes      | Run `php artisan key:generate` locally and set, or use Railway’s generate. |
| `APP_ENV`       | Yes      | `production` |
| `APP_DEBUG`     | Yes      | `false` |
| `APP_URL`       | Yes      | Full URL of the app (e.g. `https://yourapp.up.railway.app`) |
| `DB_CONNECTION` | Yes      | `pgsql` |
| `DATABASE_URL`  | Yes      | PostgreSQL connection URL (provided by Railway when you add Postgres). |

## Optional

- `CACHE_STORE=redis` and `REDIS_URL` if you add Redis.
- `SESSION_DRIVER=database` (default) or `redis` for production.

## Storage

- Run `php artisan storage:link` once if you use file uploads. Not required for embed-only usage.
