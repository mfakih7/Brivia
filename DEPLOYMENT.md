# BRIVIA — Deployment guide (host-neutral)

No hosting provider has been chosen, so this guide is a **host-neutral recipe**. **It has not been tested on a real host.** Adapt the paths and service manager to the chosen server, and record the actual procedure once it has been verified. Deployment itself requires separate, explicit owner authorisation.

## 1. Server requirements

- **PHP 8.3+** (Laravel 13 minimum). Required extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd` (JPEG/PNG/WebP), `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`. Enable **OPcache**.
- **MySQL 8** (InnoDB, utf8mb4). Development used MariaDB 10.4.
- A web server (Nginx or Apache) whose **document root is the `public/` directory only**.
- Composer 2. Node.js is needed only at build time (on CI or the build machine); it never runs on the web server.
- A process manager (systemd or Supervisor) for the **queue worker**, and **cron** for the scheduler.
- **HTTPS** with a valid certificate.
- Recommended `php.ini` settings:

| Setting | Value |
| --- | --- |
| `upload_max_filesize` | `6M` |
| `post_max_size` | `8M` |
| `memory_limit` | `256M` or more (image processing raises it to 512M temporarily) |
| `expose_php` | `Off` |
| `display_errors` | `Off` |

## 2. Production environment (`.env`)

Never commit `.env`, and never paste secrets into tickets or chat.

```dotenv
APP_NAME=BRIVIA
APP_ENV=production
APP_DEBUG=false
APP_KEY=                      # generate ONCE with `php artisan key:generate`; rotating it invalidates sessions and encrypted cookies
APP_URL=https://your-domain   # also defines the trusted Host header

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=brivia
DB_USERNAME=brivia_app        # least privilege: SELECT/INSERT/UPDATE/DELETE (+ ALTER/CREATE/INDEX for migrations)
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp              # or the provider's transport
MAIL_HOST=... MAIL_PORT=... MAIL_USERNAME=... MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@your-domain
MAIL_FROM_NAME="BRIVIA"

BRIVIA_STAFF_NOTIFICATION_EMAILS=team@your-domain
BRIVIA_STAFF_TIMEZONE=Asia/Beirut
BRIVIA_HSTS=false             # set to true only after HTTPS works for the domain AND all subdomains
TRUSTED_PROXIES=              # load balancer / CDN IPs, if any
# PUBLIC_STORAGE_URL=         # optional CDN for /storage images
```

## 3. Release procedure (zero-surprise, no destructive commands)

Build on a CI or build machine:

```sh
composer install --no-dev --optimize-autoloader
npm ci
npm run build                 # produces public/build (no source maps)
```

Then on the server, for each new release:

1. **Back up first:** a database dump plus `storage/app/public` (see §5).
2. Upload the new release into a new directory (for example `releases/2026-10-09-1`), with `vendor/` and `public/build/`.
3. Link the shared items into the release: `.env`, `storage/`, and `public/storage` → `storage/app/public`.
4. Set permissions: the web user can write only `storage/` and `bootstrap/cache/`. Code is read-only.
5. Run:
   ```sh
   php artisan migrate --force          # additive migrations only
   php artisan optimize                 # config/route/view/event caches
   php artisan storage:link             # first deploy only
   ```
6. Switch the `current` symlink to the new release and reload PHP-FPM.
7. `php artisan queue:restart`, so workers pick up the new code.
8. **Health check:**
   - `GET /up` returns 200.
   - The home page loads, `/admin/login` loads, and the response headers include the CSP.
   - `php artisan schedule:list` lists both scheduled tasks.

**Never run** `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `db:seed --class=DemoContentSeeder`, `npm run dev` or `php artisan serve` in production.

**First deploy only:**
- Run `php artisan db:seed --force`. It adds settings and **draft** starter content and is idempotent.
- Then run `php artisan brivia:create-owner` (interactive).

## 4. Background processes

**Queue worker** (Supervisor example):

```ini
[program:brivia-queue]
command=php /var/www/brivia/current/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
stopwaitsecs=60
```

**Scheduler** (cron, every minute):

```cron
* * * * * cd /var/www/brivia/current && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs two tasks:
- `brivia:notifications:dispatch` (every minute): sends due reminders and recovers lost email jobs.
- `brivia:media-cleanup` (daily).

If the worker or cron stops, emails stay **pending** (visible in Admin → Email deliveries) and are sent once they are running again. Business records are never affected.

## 5. Backups and restore

**What to back up:**
- the database
- `storage/app/public` (approved images)
- the production `.env`, stored separately in a password manager or secret store

**Encrypt and restrict access** to all backups.

| Item | How |
| --- | --- |
| Database | `mysqldump --single-transaction --routines brivia \| gzip > brivia-$(date +%F).sql.gz` |
| Images | Archive `storage/app/public` |
| Retention | Proposed 30 days, pending owner approval |

**Restore drill** (perform before launch and periodically, on a **separate** database):

```sh
mysql -e "CREATE DATABASE brivia_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip < brivia-YYYY-MM-DD.sql.gz | mysql brivia_restore_check
```

Point a staging copy at the restored database and verify that sign-in, the content and the enquiries are present. Record the date and result.

## 6. Rollback

- **Code:** point `current` back to the previous release directory, reload PHP-FPM, then run `php artisan queue:restart`.
- **Database:** all migrations so far are additive. If a future release includes a non-additive migration, plan the rollback before deploying it. Prefer forward fixes over `migrate:rollback`, and restore from backup only as a last resort.
- **Assets:** each release keeps its own `public/build`, so rolling back the code also rolls back the assets.

## 7. Security checklist before go-live

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`, and HTTPS with HTTP→HTTPS redirect at the web server.
- [ ] `SESSION_SECURE_COOKIE=true`. Enable `BRIVIA_HSTS=true` only once all subdomains serve HTTPS.
- [ ] Document root is `public/`. `.env`, `storage/`, `vendor/` and `.git` are not reachable over HTTP. `expose_php=Off`.
- [ ] Response headers checked: CSP (no `unsafe-inline`/`unsafe-eval`), `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`.
- [ ] `robots.txt` allows indexing and lists the sitemap. Public pages carry `index, follow`. `/admin` is noindex.
- [ ] The database user has least privilege. Backups and a restore drill are done.
- [ ] Queue worker and cron are running. A controlled test email reached a staff inbox, with no real prospects contacted.
- [ ] Owner-approved content, privacy and terms are published. **No demo content:** the demo seeder and its cleanup run only in `local` or `testing`. Never copy a local database that contains demo content into production. If local data must be migrated, first run `php artisan brivia:demo-content:remove` locally and confirm that the `demo_records` table is empty.
- [ ] `composer audit` and `npm audit` are clean, or any advisories have been evaluated.
- [ ] At least one owner account with a strong, unique password stored in a password manager. (Two-factor authentication was removed at the owner's request; consider network-level protection for `/admin` such as an IP allowlist or VPN if the host supports it.)

## 8. Dependency updates

Monthly, and whenever an advisory is published:
1. Run `composer audit` and `npm audit`.
2. Apply patch and minor updates on a branch and run `php artisan test` and `npm run build`.
3. Evaluate major upgrades separately (read the upgrade guides). Never use `npm audit fix --force` blindly.
4. Record the result in `docs/PROGRESS.md` or the operations log.
