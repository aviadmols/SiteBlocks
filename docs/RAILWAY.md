# Railway Deployment

## PHP requirements

- **PHP:** 8.2 (locked in `composer.json` platform so the lock file is compatible).
- **Extensions:** `intl`, `zip`, and `pdo_pgsql` are required (Filament/OpenSpout + PostgreSQL). **You must set this in Railway:**
  - **Variable:** `RAILPACK_PHP_EXTENSIONS`
  - **Value:** `intl,zip,pdo_pgsql`
  - Without `intl`/`zip`, the build fails; without `pdo_pgsql`, `php artisan migrate` fails with "could not find driver".

## Build

- **Build command:** `composer install --no-dev --optimize-autoloader`
- Do not run `php artisan migrate` during build (PostgreSQL may not be available).

## Start

- **Start command (set in Railway → Service → Settings → Deploy):**
  ```bash
  php artisan serve --host=0.0.0.0 --port=$PORT
  ```
- A `Procfile` in the repo also defines this; some stacks use it automatically.
- Railway injects `PORT`; the app **must** listen on `0.0.0.0` and `$PORT`, otherwise you get **502 Application failed to respond**.

## Migrations

Run after deployment (when DB is available):

```bash
php artisan migrate --force
```

Use Railway's shell or a one-off run. Do not run migrations in the build phase.

## Required environment variables

For the **full list and explanations** (including Postgres vs App service), see [RAILWAY_ENV.md](RAILWAY_ENV.md).

| Variable                   | Required | Description |
|----------------------------|----------|-------------|
| `RAILPACK_PHP_EXTENSIONS`  | **Yes**  | Set to `intl,zip,pdo_pgsql` (PostgreSQL driver required for migrate and DB). |
| `APP_KEY`                  | Yes      | Run `php artisan key:generate` locally and set, or use Railway’s generate. |
| `APP_ENV`       | Yes      | `production` |
| `APP_DEBUG`     | Yes      | `false` |
| `APP_URL`       | Yes      | Full URL of the app (e.g. `https://yourapp.up.railway.app`) |
| `DB_CONNECTION` | Yes      | `pgsql` (must be set in the **app** service; otherwise Laravel defaults to sqlite and returns 500). |
| `DATABASE_URL`  | Yes      | PostgreSQL connection URL. In the **app** service: set as Reference from Postgres, or use Railway's automatic injection when Postgres is linked. |

## Optional

- `CACHE_STORE=redis` and `REDIS_URL` if you add Redis.
- `SESSION_DRIVER=database` (default) or `redis` for production.

## Storage

- Run `php artisan storage:link` once if you use file uploads. Not required for embed-only usage.

---

## 502 "Application failed to respond" – troubleshooting

1. **Start command**  
   In Railway: **Service → Settings → Deploy**. Set **Custom Start Command** to:
   ```bash
   php artisan serve --host=0.0.0.0 --port=$PORT
   ```
   If this is empty or wrong, the app may not listen and you get 502.

2. **Target port**  
   **Service → Settings → Public Networking**. Ensure **Target Port** is the same as the port your app uses (Railway usually sets `PORT`, often 8080 or similar). If Target Port doesn’t match, traffic won’t reach the app.

3. **Deploy logs**  
   In **Deployments → latest deploy → View Logs**, check for PHP errors (missing extension, `APP_KEY`, database connection). Fix any fatal error so the process can start.

4. **Variables**  
   Confirm `RAILPACK_PHP_EXTENSIONS=intl,zip`, `APP_KEY`, `APP_ENV=production`, `DATABASE_URL` (or `DB_*`) are set. Missing vars can cause boot failure and 502.

---

## 500 Internal Server Error – troubleshooting

1. **See the real error**  
   In Railway **Deployments → View Logs**, check the **runtime** logs when you open the site. The 500 response is usually logged with the PHP exception (e.g. missing `APP_KEY`, database connection failed, table not found).

2. **Variables in the app service**  
   Ensure these are set **in the Laravel app service** (not only in Postgres):  
   - `DB_CONNECTION=pgsql` – without this, Laravel uses sqlite and fails.  
   - `DATABASE_URL` – reference from Postgres or auto-injected when linked.  
   - `APP_KEY`, `APP_URL` (e.g. `https://siteblocks-production.up.railway.app`).  
   Full list: [RAILWAY_ENV.md](RAILWAY_ENV.md).

3. **Migrations and seed**  
   After first deploy, run in the **app** service Shell:
   ```bash
   php artisan migrate --force
   php artisan db:seed
   ```
   Without migrations, the `sessions` and `users` tables don't exist → 500 on login and other web pages. Without seed, there is no user to log in with.

4. **Test without session/views**  
   Open: `https://your-app.up.railway.app/api/ping`  
   - If you get `{"ok":true}` → the app boots; the 500 is likely from the main page (session, DB, or view).  
   - If `/api/ping` also returns 500 → the error is in bootstrap (env, config, or DB connection in a service provider).
