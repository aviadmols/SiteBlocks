# Railway – משתני סביבה לשירות האפליקציה (Laravel)

המשתנים הבאים צריכים להיות מוגדרים **בשירות האפליקציה (Laravel)** ב־Railway, לא בשירות Postgres.

## חובה – אפליקציה

| Variable    | Value | Description |
|------------|-------|-------------|
| `APP_ENV`  | `production` | סביבת ריצה. |
| `APP_DEBUG`| `false` | לא להפעיל debug בפרודקשן. |
| `APP_URL`  | `https://siteblocks-production.up.railway.app` | כתובת האפליקציה (התאם לשם הדומיין שלך). |
| `APP_KEY`  | `base64:...` | מפתח הצפנה. הרץ `php artisan key:generate` מקומית והעתק, או השתמש ב־generate ב־Railway. |

## חובה – מסד נתונים (בשירות האפליקציה)

| Variable         | Value | Description |
|------------------|-------|-------------|
| `DB_CONNECTION`  | `pgsql` | **חשוב.** בלי זה Laravel משתמש ב־sqlite (ברירת מחדל) ונגרם 500. |
| `DATABASE_URL`   | *(Reference)* | כתובת חיבור ל־Postgres. ב־Railway: **Variables → Add Variable → Reference** → בחר את שירות Postgres → `DATABASE_URL`. אם קישרת את Postgres לשירות האפליקציה, Railway מזריק `DATABASE_URL` אוטומטית – אז רק וודא ש־`DB_CONNECTION=pgsql`. |

## חובה – Build

| Variable                  | Value    | Description |
|---------------------------|----------|-------------|
| `RAILPACK_PHP_EXTENSIONS` | `intl,zip` | הרחבות PHP לבנייה. בלי זה ה־build נכשל. |

## אופציונלי

| Variable          | Value       | Description |
|-------------------|-------------|-------------|
| `SESSION_DRIVER`  | *(לא חובה)* | ב־production ברירת המחדל היא `file` – דף הלוגין והאפליקציה עולים גם בלי טבלת sessions. אחרי הרצת `migrate` אפשר להגדיר `SESSION_DRIVER=database` ל־sessions קבועים. |

---

## הבהרה: שירות Postgres מול שירות האפליקציה

רשימת משתנים כמו:

- `DATABASE_PUBLIC_URL="${{Postgres.DATABASE_PUBLIC_URL}}"`
- `PGHOST="${{Postgres.PGHOST}}"`, `PGPASSWORD`, `PGUSER`, וכו'

שייכים ל־**שירות Postgres** (או להגדרות הפנימיות של Railway). **אל תעתיק אותם לשירות האפליקציה.**

בשירות **האפליקציה (Laravel)** מספיק:

1. `DB_CONNECTION=pgsql`
2. `DATABASE_URL` – כהפניה (Reference) מ־שירות Postgres ל־`DATABASE_URL`, או אוטומטי אם Postgres מקושר.

האפליקציה משתמשת ב־`config/database.php` שקורא ל־`DATABASE_URL` (או `DB_URL`) ו־`DB_CONNECTION`.

---

## אחרי דיפלוי ראשון – חובה

אחרי שהאפליקציה עולה, הרץ **פעם אחת** ב־Shell של שירות האפליקציה (Railway → Service → Shell):

```bash
php artisan migrate --force
php artisan db:seed
```

- **בלי מיגרציות** – טבלאות `users` ו־`sessions` לא קיימות → דפי web (login, דף הבית) מחזירים **500**.
- **בלי seed** – אין משתמש להתחברות (למשל `aviadmols@gmail.com` / `987654321`).

אחרי `migrate` ו־`db:seed` דפי login ושאר האפליקציה אמורים לעבוד.
