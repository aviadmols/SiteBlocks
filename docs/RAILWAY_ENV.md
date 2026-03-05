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
| `DB_CONNECTION`  | `pgsql` | **חשוב.** הגדר כ־**Variable רגיל** (לא Secret), כי ה־build מריץ `config:cache` וצריך את הערך. בלי זה: 500 או "secret DB_CONNECTION: not found" ב־build. |
| `DATABASE_URL`   | *(Reference)* | כתובת חיבור ל־Postgres. ב־Railway: **Variables → Add Variable → Reference** → בחר את שירות Postgres → `DATABASE_URL`. אם קישרת את Postgres לשירות האפליקציה, Railway מזריק `DATABASE_URL` אוטומטית – אז רק וודא ש־`DB_CONNECTION=pgsql`. |

## חובה – Build

| Variable                  | Value    | Description |
|---------------------------|----------|-------------|
| `RAILPACK_PHP_EXTENSIONS` | `intl,zip,pdo_pgsql` | הרחבות PHP: intl, zip ל־Filament; **pdo_pgsql** לחיבור ל־PostgreSQL. בלי pdo_pgsql תקבל "could not find driver" בהרצת migrate. |

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

אחרי שהאפליקציה עולה, הרץ **פעם אחת** ב־Shell של שירות האפליקציה (Railway → Service → Shell).

**חשוב:** הרץ **פקודה אחת בכל פעם** (לא להדביק את שתיהן יחד):

1. הרץ והמתן לסיום:
   ```bash
   php artisan migrate --force
   ```
2. אחרי שהפקודה מסתיימת, הרץ:
   ```bash
   php artisan db:seed
   ```

אם תדביק את שתי השורות יחד, Railway עלול להעביר "php" כארגומנט ל־migrate ותקבל: `No arguments expected for "migrate" command, got "php"`.

- **בלי מיגרציות** – טבלאות `users` ו־`sessions` לא קיימות → דפי web (login, דף הבית) מחזירים **500**.
- **בלי seed** – אין משתמש להתחברות (למשל `aviadmols@gmail.com` / `987654321`).

אחרי `migrate` ו־`db:seed` דפי login ושאר האפליקציה אמורים לעבוד.

---

## 500 אחרי שליחת הלוגין (בפופאפ)

אם **דף הלוגין נטען** אבל אחרי הזנת אימייל וסיסמה ולחיצה על Sign in מופיע **500 Server Error** – הסיבה כמעט תמיד היא שבשרת **לא הורצו מיגרציות ו/או סידר**.

בשליחת הטופס האפליקציה מחפשת את המשתמש בטבלת `users`. אם הטבלה לא קיימת או שהמשתמש לא נוצר → מתקבלת שגיאת 500.

**פתרון:** ב־Railway עבור **שירות האפליקציה (Laravel)** (לא שירות Postgres):

1. **Settings** או **Shell** → פתח Shell של השירות.
2. הרץ **פקודה אחת**, חכה שתסתיים, ואז הרץ את השנייה:
   - קודם: `php artisan migrate --force`
   - אחר כך: `php artisan db:seed`
3. נסה שוב להתחבר עם `aviadmols@gmail.com` / `987654321`.

אם עדיין 500 – ב־**Deployments → View Logs** (לוגים בזמן ריצה) תופיע השגיאה המדויקת (למשל חיבור DB נכשל – אז וודא `DB_CONNECTION=pgsql` ו־`DATABASE_URL` בשירות האפליקציה).

---

## "could not find driver" בהרצת migrate

אם בהרצת `php artisan migrate --force` מתקבלת השגיאה **could not find driver (Connection: sqlite, SQL: ... pg_class ...)**:

1. **הוסף את הרחבת PostgreSQL ל־PHP:** בשירות האפליקציה הגדר:
   - `RAILPACK_PHP_EXTENSIONS` = `intl,zip,pdo_pgsql`
   (בלי `pdo_pgsql` ל־PHP אין דרייבר ל־PostgreSQL.)
2. **בצע Redeploy** כדי שה־build ירוץ מחדש עם ההרחבה.
3. וודא ש־**DB_CONNECTION=pgsql** ו־**DATABASE_URL** מוגדרים באותו שירות (כך שגם ב־Shell הפקודה migrate תשתמש ב־Postgres).
4. הרץ שוב: `php artisan migrate --force`, ואז `php artisan db:seed`.

---

## "could not find driver" / "Connection: sqlite" בדפי האתר (למשל /livewire/update)

אם השגיאה מופיעה **בעת גלישה** (לא רק ב־migrate) – Laravel משתמש ב־sqlite כי **בשירות האפליקציה לא הוגדרו** `DB_CONNECTION` ו־`DATABASE_URL`.

- **פתרון:** בשירות האפליקציה (Laravel) ב־Variables הוסף/עדכן:
  - `DB_CONNECTION` = `pgsql`
  - `DATABASE_URL` = Reference משירות Postgres (או קישור את Postgres לשירות כדי ש־Railway יזריק אוטומטית)
- **Redeploy** אחרי שינוי משתנים.
- בפרודקשן ה־cache מוגדר כברירת מחדל ל־`file` כדי שלא יהיה תלות ב־DB ל־cache; גם כך חובה להגדיר את חיבור ה־DB להתחברות ולנתונים.

**אם עדיין מופיע "Connection: sqlite":** ה־build מריץ `config:cache` כש־DATABASE_URL אולי עדיין לא זמין, ולכן ה־config השמור "זוכר" חיבור לא נכון. ה־Procfile מריץ `php artisan config:clear` לפני ההפעלה כדי שה־config ייבנה מחדש מה־env בזמן ריצה. וודא ש־**DATABASE_URL** אכן מוזרק משירות Postgres (הקישור בין SiteBlocks ל־Postgres אמור לעשות זאת).
