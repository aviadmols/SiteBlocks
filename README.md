# Embeddable Blocks SaaS

Production-ready Laravel application that delivers embeddable blocks/widgets to client sites via a single `<script>` tag. Multi-tenant by Site; first block: **Shopify Add To Cart Counter**.

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL (or SQLite for local)
- Node.js / npm (for Breeze/Filament assets)

## Local setup

1. **Clone and install**
   ```bash
   cd ImportData
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database**
   - PostgreSQL: set `DB_CONNECTION=pgsql` and `DB_*` or `DATABASE_URL` in `.env`.
   - Or SQLite: `touch database/database.sqlite` and set `DB_CONNECTION=sqlite`.
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

3. **Frontend (Breeze/Filament)**
   ```bash
   npm install
   npm run build
   ```

4. **Run**
   ```bash
   php artisan serve
   ```
   - App: http://localhost:8000  
   - Admin: http://localhost:8000/admin (login with seeded user: `admin@example.com` / `password`)  
   - Embed script: http://localhost:8000/embed.js?site=YOUR_SITE_KEY  

### Key .env variables

| Variable       | Description |
|----------------|-------------|
| `APP_URL`      | Full URL of the app (used in embed snippet). |
| `DB_CONNECTION`| `pgsql` or `sqlite`. |
| `DB_*` / `DATABASE_URL` | Database connection. |
| `CACHE_STORE`  | `file` or `redis` (optional). |

## Embed usage

For each Site, the admin shows an embed snippet:

```html
<script async src="https://APP_DOMAIN/embed.js?site=SITE_KEY"></script>
```

- The loader fetches config from `/api/public/sites/{SITE_KEY}/config` and runs each active block in isolation.
- Add `?debug=1` to the script URL for console logging.

## Public API (no auth)

- `GET /embed.js?site=SITE_KEY` – Loader script.
- `GET /api/public/sites/{site_key}/config` – Config + active blocks (cache 60s, ETag).
- `POST /api/public/events` – Analytics ingest (body: `site_key`, `event_name`, `page_url`, …).
- `GET /api/public/shopify/count?site_key=…&product_id=…|variant_id=…` – Add-to-cart count.
- `POST /api/public/shopify/add-to-cart` – Increment count (body: `site_key`, `block_id?`, `product_id?`, `variant_id?`, `page_url`).

All public endpoints are rate-limited; IP and User-Agent are hashed and not stored in raw form.

## Railway deployment

1. **New project** – Connect repo; add **PostgreSQL** from Railway dashboard.

2. **Variables** – Set in Railway (Project → Variables):
   - **`RAILPACK_PHP_EXTENSIONS`** = **`intl,zip`** (required; otherwise the build fails with missing ext-intl/ext-zip)
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://YOUR_APP_UP.railway.app` (or your custom domain)
   - `DB_CONNECTION=pgsql`
   - `DATABASE_URL` – use the Postgres URL from Railway (auto-set if you link the Postgres service)
   - `CACHE_STORE=file` (or `redis` if you add Redis)

3. **Build**
   - Build command: `composer install --no-dev --optimize-autoloader`
   - Do **not** run migrations during build (DB may be unreachable). Run migrations after deploy (see below).

4. **Start**
   - Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - Or use a Procfile: `web: php artisan serve --host=0.0.0.0 --port=$PORT`

5. **Migrations**
   - After first deploy, run migrations from Railway shell or a one-off job:
     `php artisan migrate --force`
   - Or add a **release** step in Nixpacks/Railway if your stack supports it.

6. **Storage**
   - If you use file storage for uploads: `php artisan storage:link` (run once). This app does not require it for the embed.

7. **Optional**
   - Cron: `php artisan schedule:run` (if you add scheduled tasks).
   - Queue worker: separate service with `php artisan queue:work` if you queue events later.

## Tests

```bash
php artisan test
```

## License

MIT.
