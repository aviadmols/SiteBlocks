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
| `DB_CONNECTION` | Yes      | `pgsql`. Set as a **plain Variable** (not Secret), so the build step (config:cache) can read it; otherwise you may get "secret DB_CONNECTION: not found". |
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

---

## Response time / slowness (e.g. spikes in the morning)

If the site is slow at certain times (e.g. high p50/p99 response times in monitoring):

1. **Cold start**  
   After a deploy or when the service was idle, the first requests hit a fresh process: PHP boots, Laravel loads config and services. That can add several seconds.  
   - **Mitigation:** Keep the service always-on (no scale-to-zero) if you need stable latency, or use a health-check URL that gets hit regularly so the process stays warm.

2. **Procfile runs `config:clear`**  
   The Procfile runs `php artisan config:clear` before `serve`, so at runtime Laravel does not use a cached config file. The app still loads config once per process; the main cost is on the **first request** after a restart.  
   - **Mitigation:** If you can run `config:cache` **after** env is available (e.g. in a start script that runs after Railway injects `DATABASE_URL`), you can cache config for that run. The current setup uses `config:clear` so the app reads `DATABASE_URL` at runtime and avoids wrong DB at build time.

3. **Session driver = database**  
   With `SESSION_DRIVER=database` (default), every request does at least one read and one write to the `sessions` table. If the DB is slow or far (network latency), that adds up.  
   - **Mitigation:** For the embed script and public API, those routes can use a different guard or no session. The admin (Filament) needs session; keeping database session is fine if the DB is fast. If you add Redis, `SESSION_DRIVER=redis` and `CACHE_STORE=redis` can reduce DB load and latency.

4. **N+1 queries in Filament**  
   List pages that show related data (e.g. Block list with `site.name`) must eager-load relations so each row doesn’t trigger an extra query.  
   - **Mitigation:** Already done: `BlockResource::getEloquentQuery()` uses `->with('site:id,name')`. Other resources that show relations in the table should use `->with('relation')` in their base query.

5. **Config endpoint for embed**  
   The public config endpoint (`/api/public/config/{site_key}`) is cached in the app (e.g. 5 minutes) and returns `Cache-Control` and `ETag`, so repeat requests are cheap. No change needed unless you shorten TTL or add more blocks per site.

6. **Database and plan**  
   On Railway, DB and app can share the same region; if the DB plan is small or under load, slow queries will increase response time. Check DB metrics and consider indexing (e.g. on `sessions` and on foreign keys used in list filters).
