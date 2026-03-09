# למה ה-DB מתאפס אחרי עדכון ב-Git (Railway)

אם **כל פעם שאתה עושה push ל-Git** המסד נתונים נראה ריק (או חוזר למצב התחלתי), הסיבה **לא** נמצאת בקוד הפרויקט. ב-repo **אין** שום פקודה שמריצה `migrate:fresh`, `migrate:reset` או מוחקת טבלאות.

הסיבות האפשריות הן **הגדרות ב-Railway** או **חיבור למסד נתונים לא יציב**.

---

## 1. מסד נתונים חדש בכל deploy (הסיבה הנפוצה)

ב-Railway, אם שירות **PostgreSQL** לא מוגדר כ-**Persistent** או שה-**אפליקציה לא מקושרת לאותו שירות Postgres** בכל deploy – האפליקציה עלולה לקבל חיבור ל-DB **ריק** או **חדש**.

### מה לבדוק

1. **Railway Dashboard** → הפרויקט → **שירות PostgreSQL**
   - וודא שיש **שירות אחד** של PostgreSQL (לא נוצר שירות חדש בכל deploy).
   - ב-**Settings** של שירות Postgres: וודא ש-**Persistent Volume** / אחסון קבוע מופעל אם יש אופציה כזו.

2. **קישור בין האפליקציה ל-Postgres**
   - שירות **האפליקציה (Laravel)** חייב להיות **מקושר** לשירות Postgres (אותו פרויקט).
   - ב-**Variables** של שירות האפליקציה:
     - `DATABASE_URL` = **Reference** מ-**Postgres** → `DATABASE_URL`  
       (כך בכל deploy אתה עדיין מחובר **לאותו** מסד נתונים.)

3. **אין "Deploy" או "Build" command שמריץ migrate:fresh**
   - ב-Railway → שירות האפליקציה → **Settings** → **Deploy** / **Build**:
   - **Build Command:** אמור להיות רק משהו כמו `composer install --no-dev --optimize-autoloader`.
   - **אל** להגדיר כאן:
     - `php artisan migrate:fresh`
     - `php artisan migrate:reset`
     - `php artisan db:seed --force` (אלא אם אתה מבין שזה מוסיף נתונים ולא מאפס)
   - **Start Command:** אמור להיות רק:
     - `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **אל** להריץ `migrate:fresh` או `migrate:reset` ב-Start Command.

---

## 2. הרצת migrate / seed אוטומטית אחרי כל deploy

אם הגדרת ב-Railway **Deploy Hooks** או **Script** שרץ אחרי כל deploy ומריץ:

- `php artisan migrate:fresh`  
- או `php artisan migrate:fresh --seed`  

זה **ימחק** את כל הטבלאות ויצור מחדש – ולכן ה-DB "מתאפס".

### מה לעשות

- **הסר** כל הרצה אוטומטית של `migrate:fresh` או `migrate:reset`.
- אחרי deploy רק **מגרנטים חדשים** רצויים:
  - `php artisan migrate --force`  
  (זה מוסיף טבלאות/עמודות חדשות, **בלי** למחוק נתונים קיימים.)

---

## 3. שני שירותי Postgres / DATABASE_URL משתנה

אם פעם יצרת שירות Postgres **חדש** (למשל שינוי שם הפרויקט או ה-service), או ש-**DATABASE_URL** לא מוגדר כ-**Reference** אלא כ-**ערך קבוע** ששינית – האפליקציה עלולה להתחבר ל-DB **אחר**, שנראה "ריק".

### מה לעשות

- וודא ש-**DATABASE_URL** בשירות האפליקציה = **Variable Reference** לשירות Postgres:
  - Add Variable → **Reference** → בחר את שירות **Postgres** → שדה `DATABASE_URL`.
- כך בכל deploy אתה תמיד מחובר **לאותו** מסד נתונים.

---

## 4. סיכום – מה וודא ב-Railway

| בדיקה | תיאור |
|--------|--------|
| שירות Postgres אחד ויציב | לא נוצר שירות Postgres חדש בכל deploy. |
| DATABASE_URL = Reference | ב-Variables של האפליקציה, `DATABASE_URL` מוגדר כ-Reference משירות Postgres. |
| אין migrate:fresh / reset | ב-Build, Start או Deploy Hooks **אין** `migrate:fresh` או `migrate:reset`. |
| רק migrate --force אחרי deploy | אם אתה רוצה מיגרציות אוטומטיות, הרץ רק `php artisan migrate --force` (ידנית ב-Shell או ב-Deploy Hook). |

---

## 5. מה כן קיים בפרויקט (ולא מאפס DB)

- **composer.json**  
  - הסקריפט `setup` כולל `migrate --force` – הוא רץ **רק** כשמריצים מקומית `composer run setup`, **לא** ב-build ב-Railway.  
  - ב-Railway ה-build הוא רק `composer install`, בלי להריץ `setup`.

- **Procfile**  
  - מריץ רק `config:clear` ו-`serve` – **אין** שם migrate או seed.

- **מיגרציות**  
  - יש רק `migrate` רגיל (up/down). `down()` מוחק טבלאות **רק** ב-`migrate:rollback`, שלא אמור לרוץ אוטומטית ב-deploy.

אם אחרי הבדיקות האלה ה-DB עדיין מתאפס – מומלץ לבדוק ב-Railway את **Deploy Logs** ו-**Settings → Deploy/Build** של שירות האפליקציה ולוודא שאין שם פקודה שמריצה `migrate:fresh` או מחליפה את `DATABASE_URL` למסד חדש.
