# Railway Deployment

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

| Variable        | Required | Description |
|-----------------|----------|-------------|
| `APP_KEY`       | Yes      | Run `php artisan key:generate` locally and set, or use Railway’s generate. |
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
