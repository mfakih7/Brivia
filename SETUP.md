# BRIVIA — Local setup

This guide covers local development and review. Production hosting is covered in [DEPLOYMENT.md](DEPLOYMENT.md) and day-to-day operation in [OPERATIONS.md](OPERATIONS.md).

## Prerequisites (verified on the development machine, 2026-10-05)

| Tool | Version used | Notes |
| --- | --- | --- |
| PHP | 8.3.29 (NTS, x64) | Laravel 13 requires PHP ≥ 8.3 |
| Laravel | 13.34.0 | |
| Composer | 2.x | |
| Node.js / npm | 24.12.0 / 11.6.2 | Needed only to build frontend assets |
| Database | MariaDB 10.4.32 (XAMPP, current local DB `brivia`); MySQL 8 is the production target | See "Database" below |

Required PHP extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd` (with JPEG, PNG and WebP support), `intl`, `mbstring`, `openssl`, `pdo_mysql` (MySQL) or `pdo_sqlite` (local), `tokenizer`, `xml`, `zip`.

### Windows / XAMPP note

XAMPP's default `php` on PATH is PHP 8.2, which **cannot** run Laravel 13. Call PHP 8.3 explicitly:

```sh
/c/php83/php.exe artisan ...
/c/php83/php.exe /c/ProgramData/ComposerSetup/bin/composer.phar ...
```

On Linux/macOS use `php` and `composer` directly.

Other Windows/XAMPP notes:
- **Start MariaDB** from the XAMPP Control Panel (MySQL → Start) before using the site. Equivalent command: `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`. A separate `MySQL80` Windows service on port 3307 also exists on this machine; it is **not** used by BRIVIA.
- **Image uploads** need `upload_max_filesize = 6M` and `post_max_size = 8M` in `C:\php83\php.ini`; the current value is 2M. The app enforces its own 5 MB limit.
- Enabling OPcache (`zend_extension=opcache`) makes local pages noticeably faster. It is disabled in the current `php.ini`.

## First-time setup

```sh
composer install
cp .env.example .env            # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan storage:link        # public/storage -> storage/app/public (approved images)
```

## Database

The specification targets **MySQL 8 (InnoDB, utf8mb4)**. The development machine now uses the local XAMPP server (MariaDB 10.4.32) with database `brivia` (see the settings below; XAMPP's default `root` account has no password and is suitable only locally). `.env.example` still defaults to SQLite, which also works for quick local setups.

To use MySQL instead, create a database and a dedicated user, then set the following in `.env`:

```sql
CREATE DATABASE brivia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'brivia'@'localhost' IDENTIFIED BY '<choose-a-strong-password>';
GRANT ALL PRIVILEGES ON brivia.* TO 'brivia'@'localhost';
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brivia
DB_USERNAME=brivia
DB_PASSWORD=<your password>
```

Run migrations and seed the draft content. Both commands are non-destructive and the seeders are idempotent:

```sh
php artisan migrate
php artisan db:seed
```

**Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset` or `db:wipe` against a database that holds real content or submissions.**

The seeders create site settings, homepage copy and **draft** services, packages, consultation types, placeholder founder cards and placeholder legal outlines. They create **no users, no passwords, no portfolio projects and no prices**.

## Optional: demo content for local review (development only)

All seeded starter content is draft, so the public site is mostly empty until you publish real content. To review every page with realistic **sample** content:

```sh
C:\php83\php.exe artisan db:seed --class=DemoContentSeeder
```

What the seeder does:
- Runs only when `APP_ENV` is `local` or `testing`; it aborts in every other environment.
- Is never called by `DatabaseSeeder` or deployment.
- Is safe to re-run: it creates nothing twice and never overwrites real content.
- Records everything it creates in the `demo_records` table.
- Creates no users, sends no email and schedules nothing.

What it adds (all published):
- 4 services, 4 packages with **illustrative** prices and 2 consultation types.
- 6 projects with generated local images: 5 labelled **Sample concept** and 1 **Earlier experience example (placeholder)**.
- 2 founder cards with **placeholder** names.
- Sample values on the About page, and sample contact details (`hello@brivia.example`, `+1 555 0100`). These fill only fields that are blank.

To remove it (guarded; local/testing only; asks for confirmation unless `--force`):

```sh
C:\php83\php.exe artisan brivia:demo-content:remove
```

Cleanup works as follows:
- It deletes only registered demo records and demo images that nothing else uses.
- Demo records that real data references (for example an enquiry) are kept and listed.
- Settings and About fields are reset to their previous value, unless you edited them after seeding.

## Create the first owner account

The command is interactive and the password is never passed as an argument:

```sh
php artisan brivia:create-owner
```

It refuses to run if an owner already exists. Invite additional staff from **Admin → Staff**.

Sign in at `/admin/login` with your email and password; you go straight to the dashboard. Two-factor authentication was removed at the owner's request on 2026-10-09.

**Forgotten password:** use *Forgot your password?* on the sign-in page. With `MAIL_MAILER=log`, the reset link (valid 30 minutes) appears in `storage/logs/laravel.log`.

## Running locally

```sh
C:\php83\php.exe artisan serve --host=127.0.0.1 --port=8090   # http://127.0.0.1:8090 (development only, never production)
npm run dev                     # optional: Vite hot reload (the CSP allows the dev server only while it runs)
C:\php83\php.exe artisan queue:work          # sends queued email (log mailer locally); run in a second terminal
C:\php83\php.exe artisan schedule:work       # runs the scheduler: due reminders + lost-job recovery (every 5 minutes, as in production)
```

- **Public site:** http://127.0.0.1:8090
- **Admin:** http://127.0.0.1:8090/admin/login

If `APP_URL` differs from the host and port you browse, everything still works, because image URLs are host-relative. Absolute links in emails and the sitemap use `APP_URL`.

Email defaults to `MAIL_MAILER=log`, so messages are written to `storage/logs/laravel.log` and nothing is sent. Do not configure a real mail provider locally unless controlled delivery tests are authorised. Without `queue:work`, submitted forms are still saved; their emails simply stay *pending* (Admin → Email deliveries) until a worker runs.

## Tests and quality checks

```sh
php artisan test                # PHPUnit; uses in-memory SQLite (never your app database)
vendor/bin/pint --test          # code style
composer validate && composer audit
npm audit
npm run build
```

The real-MySQL concurrency test is skipped unless a **separate** MySQL test database is configured. On this machine, `brivia_test` exists for that purpose:

```powershell
$env:BRIVIA_TEST_MYSQL_DATABASE="brivia_test"; C:\php83\php.exe artisan test --filter=MySqlConcurrencyTest; Remove-Item Env:BRIVIA_TEST_MYSQL_DATABASE
```

See "MySQL concurrency test" in [OPERATIONS.md](OPERATIONS.md).

## Troubleshooting

- **"This application requires a PHP version matching ^8.3.0"**: you are using XAMPP's PHP 8.2. Use `/c/php83/php.exe`.
- **Images do not appear**: run `php artisan storage:link`. On Windows this may require an elevated terminal or Developer Mode to create the symlink.
- **Styles missing / Vite manifest error**: run `npm run build`, or keep `npm run dev` running.
- **Locked out by rate limiting**: wait 60 seconds. Limits are 5 sign-in attempts per minute per email and IP.
- **419 Page Expired**: the session or CSRF token expired. Refresh the page and resubmit.
