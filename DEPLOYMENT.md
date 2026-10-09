# BRIVIA — Deployment guide (Namecheap shared hosting)

This guide is for whoever runs the production deployment: the owner or a developer with cPanel and GitHub access.

| Item | Value |
| --- | --- |
| Production URL | https://brivia.tech |
| Source | https://github.com/mfakih7/Brivia.git, branch `main` |
| cPanel clone | `/home/fitcbfra/repositories/brivia` |
| Live application | `/home/fitcbfra/brivia-app` (document root: `/home/fitcbfra/brivia-app/public`) |
| PHP | `/opt/alt/php83/usr/bin/php` (8.3.35) |
| Composer | `/home/fitcbfra/bin/composer` (2.10.3) |
| Database and user | `fitcbfra_brivia` |
| Build host | GitHub Actions; Node/npm are **not** available on the server |

> **Status:** prepared and validated locally (see "What was verified locally" at the end). **No step has been run on the hosting account yet.** Items marked ⚠ need confirming on the server.

## 1. How a release works (manual, never automatic)

```
push to main ──► GitHub Actions "Frontend build" ──► release build-<full SHA>
                                                     (assets + SHA-256)
   cPanel → Git Version Control → Manage → Pull or Deploy:
     1. "Update from Remote"   (the clone fetches main)
     2. "Deploy HEAD Commit"   (runs .cpanel.yml → scripts/deploy.sh)
```

- **Pushing to `main` never changes the live site.** It only builds frontend assets on GitHub.
- A deployment happens **only** when someone presses *Deploy HEAD Commit* in cPanel.
- Always wait for the **Frontend build** workflow to show a green check for that exact commit before deploying. If you deploy too early, the script stops with "Frontend build … is not available yet" and **changes nothing**. Wait for the build, then deploy again.
- The script deploys the clone's exact `HEAD` commit, and downloads only the release `build-<that commit's full SHA>`, never "latest".

### Release checklist

1. Run the tests locally (`php artisan test`) and merge or push to `main`.
2. In GitHub, open **Actions → Frontend build** and wait for the run for your commit to succeed. The run summary shows the commit and the tag `build-<sha>`.
3. In cPanel, open **Git Version Control**, then **Manage** for `brivia`, then the **Pull or Deploy** tab.
4. Click **Update from Remote**, and check that *HEAD Commit* shows your commit.
5. Click **Deploy HEAD Commit**. The deployment log is shown in cPanel and kept in `~/.brivia-deploy/logs/`.
6. Open https://brivia.tech and `/admin/login`, and check the change you deployed.

## 2. GitHub settings (one-time)

**Repository → Settings → Actions → General:**
- **Actions permissions:** allow actions. The workflow uses `actions/checkout` and `actions/setup-node` (GitHub-owned).
- **Workflow permissions:** either setting works. The workflow requests `contents: write` for its single job and uses the built-in `GITHUB_TOKEN`. **Do not add a personal access token.**
- If you add **tag protection rules or rulesets**, they must allow GitHub Actions to create tags matching `build-*`, or publishing will fail.

**What the workflow does:**
- Runs on push to `main` and on manual **Run workflow**.
- Uses the Node version in `.nvmrc` (22 LTS). Vite 8 requires `^20.19.0 || >=22.12.0`.
- Runs `npm ci` and `npm run build`, with no `.env` and no secrets.
- Packages only `public/build`, including a `build-info.json` that records the source SHA, plus a `.sha256` file.
- Uploads both to a **draft** release, downloads and verifies them, then **publishes** release `build-<SHA>` with its tag pointing at that exact commit. The release is not marked "latest".

**Reruns are safe:**

| Existing state | What the rerun does |
| --- | --- |
| A complete published release for the commit | Nothing |
| An unfinished draft from a failed run | Replaces it |
| A published-but-incomplete release, or a tag pointing elsewhere | Fails loudly; it is never overwritten |

**Public assets:** the release contains only the same CSS, JS and font files the website serves publicly anyway.

## 3. First-time server setup

Do these steps **once**, in cPanel **Terminal** (or SSH), before the first *Deploy HEAD Commit*. Never paste passwords or keys into chat, tickets or GitHub.

### 3.1 Prerequisites in cPanel
- **MultiPHP Manager:** set `brivia.tech` to **PHP 8.3**. ⚠ Confirm that the extensions needed by `composer.lock` are enabled: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd` (with JPEG/PNG/WebP), `intl`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`. The deploy script also runs `composer check-platform-reqs` and stops if one is missing.
- **MultiPHP INI Editor** for `brivia.tech`:

  | Setting | Value |
  | --- | --- |
  | `upload_max_filesize` | `6M` |
  | `post_max_size` | `8M` |
  | `memory_limit` | `256M` |
  | `display_errors` | `Off` |
  | `expose_php` | `Off` |

- **Domains:** confirm the document root is `/home/fitcbfra/brivia-app/public`.
- **SSL/TLS Status:** run AutoSSL for `brivia.tech` and `www.brivia.tech`. Under **Domains**, switch **Force HTTPS Redirect** on once the certificate is active.
- **MySQL Databases:** database `fitcbfra_brivia` and user `fitcbfra_brivia` exist, and the user has **ALL PRIVILEGES** on that database only.

### 3.2 Clone the repository (cPanel → Git Version Control → Create)
- Clone URL `https://github.com/mfakih7/Brivia.git`, repository path `/home/fitcbfra/repositories/brivia`, branch `main`.
- The repository contains `.cpanel.yml`, which tells cPanel to run `scripts/deploy.sh`.

### 3.3 Create the production `.env` (before the first deploy)

```bash
mkdir -p /home/fitcbfra/brivia-app
cp /home/fitcbfra/repositories/brivia/.env.production.example /home/fitcbfra/brivia-app/.env
chmod 600 /home/fitcbfra/brivia-app/.env

# Generate APP_KEY ONCE. The app code isn't deployed yet, so use plain PHP; artisan isn't needed.
/opt/alt/php83/usr/bin/php -r 'echo "base64:".base64_encode(random_bytes(32)), PHP_EOL;'
```

Open `/home/fitcbfra/brivia-app/.env` in the cPanel File Manager editor and replace every `<PLACEHOLDER>`:
- the `APP_KEY` you just generated
- `DB_PASSWORD`
- the mail settings (§6)
- `BRIVIA_STAFF_NOTIFICATION_EMAILS`

Keep a copy of the whole `.env` in your password manager.

**APP_KEY rules:**
- Generate it once and never change it.
- Changing it signs everyone out and makes encrypted values (sessions, cookies) unreadable.
- The deploy script **never** generates or replaces it, and refuses to deploy if it is missing.

### 3.4 First deployment
1. Wait for a green **Frontend build** for the current `main` commit.
2. Optionally, run a dry run first in the Terminal. It performs every check, stages the release, installs dependencies, and lists what *would* change (via `rsync --dry-run`), without touching the live app or taking a backup:
   ```bash
   BRIVIA_DEPLOY_DRY_RUN=1 /bin/bash /home/fitcbfra/repositories/brivia/scripts/deploy.sh
   ```
3. In cPanel, run **Update from Remote**, then **Deploy HEAD Commit**.
4. Run these **once** after the first successful deploy:
   ```bash
   cd /home/fitcbfra/brivia-app
   /opt/alt/php83/usr/bin/php artisan db:seed --force          # site settings + DRAFT starter content (idempotent; no users, no demo data)
   /opt/alt/php83/usr/bin/php artisan brivia:create-owner      # interactive; you type the password, nothing is stored in shell history
   ```
   - There are **no default credentials**: the owner account exists only after you create it here.
   - Additional staff are invited from **Admin → Staff**.
   - **Never** run `db:seed --class=DemoContentSeeder` in production. It refuses anyway, because it only runs in `local` and `testing`.

### 3.5 Cron (cPanel → Cron Jobs)

Namecheap allows cron jobs **at most every 5 minutes**, so add exactly **one** job:

```
*/5 * * * *  cd /home/fitcbfra/brivia-app && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Every scheduled task is aligned to that 5-minute rhythm, so none is skipped (an automated test enforces this):

| Task | When | Purpose |
| --- | --- | --- |
| `brivia:notifications:dispatch` | every 5 min | Queues due emails, including 24 h reminders, and recovers lost jobs |
| `queue:work --stop-when-empty --max-time=240 --tries=3 --max-jobs=200` | every 5 min, only when `BRIVIA_SCHEDULER_QUEUE_WORKER=true` | Sends queued emails |
| `brivia:media-cleanup` | daily 03:10 UTC | Removes unreferenced replaced images |

**About the queue worker:**
- It is **bounded, not a daemon**: it exits when the queue is empty or after 240 seconds, before the next cron run.
- `withoutOverlapping(10)` stops two workers running at once. Its lock lives in the database cache and expires after 10 minutes if a run dies.
- Emails are therefore sent within about 5 minutes. Delivery state is visible in **Admin → Email deliveries**.

⚠ Verify on the host: after about 10 minutes, `php artisan schedule:list` shows the tasks, and a test enquiry's acknowledgement moves from *pending* to *sent* (with controlled delivery to your own inbox).

## 4. What `scripts/deploy.sh` does

The script logs to `~/.brivia-deploy/logs/` (the last 30 runs are kept).

1. **Lock:** takes `flock` on `~/.brivia-deploy/deploy.lock`, or falls back to an atomic `mkdir` lock. A second deploy started meanwhile stops immediately.
2. **Safety checks:**
   - The target must end in `/brivia-app`.
   - Explicit PHP 8.3 and Composer paths.
   - The clone's exact `HEAD` commit is resolved; a warning is shown if it isn't on `main`.
3. **Preflight:**
   - The live `.env` exists, with a valid `APP_KEY`.
   - Warnings are shown if `APP_ENV` isn't `production` or `APP_DEBUG` isn't `false`.
4. **Frontend build:** downloads `build-<SHA>/brivia-frontend-<SHA>.tar.gz` and its `.sha256` from GitHub, then:
   - verifies the size and SHA-256
   - allows only regular files and directories under `build/` (no `..`, absolute paths or links)
   - checks that `build-info.json` records the same SHA
   - checks that the Vite manifest has both entries and every file it references
5. **Staging:** `git archive` of exact `HEAD`, so Git metadata, untracked files and local `.env` files are never deployed.
   - Development material is removed: `tests`, `docs`, `scripts`, `.github`, `node_modules`, Vite sources and config, `package*.json`, `phpunit.xml` and editor files.
   - The verified assets are added.
   - `composer check-platform-reqs --no-dev`, then `composer install --no-dev --prefer-dist --optimize-autoloader` **from `composer.lock`**.
6. **Database backup:** `mysqldump --single-transaction` to `~/brivia-backups/brivia-db-<time>-before-<sha>.sql.gz` (mode 600; the last 10 are kept). The password goes in a temporary mode-600 options file, never on the command line. **If the backup fails, the deploy stops before any change.**
7. **Switch:**
   - `artisan down` (when an app already exists).
   - `rsync -a --delete` into `/home/fitcbfra/brivia-app`. These are **protected** (never overwritten or deleted): `.env*`, `storage/`, `public/storage`, `public/.well-known/`, `public/.user.ini`, `php.ini`, `error_log`, a root `.htaccess` and cached `bootstrap/cache/*.php`.
   - The cPanel MultiPHP handler block is restored in `public/.htaccess`, so PHP 8.3 stays selected.
8. **Laravel tasks:**
   - `package:discover`, then clear the file caches (config, route, view, event).
   - **`migrate --force`**.
   - `storage:link` if missing.
   - `optimize` (config, route, view and event caches) and `queue:restart`.
   - Record the release in `storage/app/deploy/current-release` and `history.log`.
   - `artisan up`.
9. **Health check:** `GET $APP_URL/up`. If it fails, the deploy is reported as failed even though the code is live.

**Never run** (by the script or by hand in production): `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `key:generate`, the demo seeder, `npm`, `php artisan serve`.

**Touches only BRIVIA paths:** `/home/fitcbfra/brivia-app`, `~/.brivia-deploy`, `~/brivia-backups` and the clone. Other applications on the account are never read or written.

**Repeat deployments** of the same or a newer commit are safe. Each deployment is a full, idempotent sync of one exact commit.

## 5. Recovery and rollback

| Situation | What to do |
| --- | --- |
| "Frontend build … not available" | Wait for the GitHub workflow for that commit, then deploy again. Nothing was changed. |
| Failure **before** the sync (checksum, manifest, Composer, platform, backup) | Nothing live changed. Fix the cause and deploy again. |
| Failure **after** the sync (for example a migration) | The site stays in **maintenance mode** on purpose. Read the log, fix the cause, and deploy again (safe). If needed, `cd /home/fitcbfra/brivia-app && /opt/alt/php83/usr/bin/php artisan up`. |
| A bad release reached production | **Roll forward:** revert the commit on `main` (`git revert`), push, wait for its build, then Update from Remote and Deploy HEAD Commit. This is the supported rollback. |

**Limits:**
- cPanel deploys the clone's `HEAD`, so "rolling back" means deploying an older state through a new commit (a revert). Every commit on `main` has its own `build-<sha>` release, so earlier states remain deployable.
- **Database migrations are forward-only in practice.** Reverting code does not undo schema changes. If a migration damaged data, restore the pre-deploy backup:
  ```bash
  gunzip < ~/brivia-backups/brivia-db-<time>-before-<sha>.sql.gz | /usr/bin/mysql -u fitcbfra_brivia -p fitcbfra_brivia
  ```
  This replaces data written after that backup, such as new enquiries, so take a fresh backup first and only restore deliberately.
- Every migration so far is additive or cleans up feature-specific data, such as the removed FAQ table. Destructive migrations need an explicit plan before release.
- Uploaded images (`storage/app/public`) are not part of the code deployment, so deploying never deletes or restores them. Back them up separately (§7).

## 6. Email

- **Mailbox:** create one for the sender in cPanel **Email Accounts** (for example `no-reply@brivia.tech`), or use Namecheap Private Email or another SMTP provider.
- **`.env` settings:** `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtps`, `MAIL_PORT=465`, `MAIL_HOST` (shown by cPanel under *Connect Devices*, for example `mail.brivia.tech`), `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS`. ⚠ Confirm the host and port shown by cPanel.
- **Deliverability:** in cPanel **Email Deliverability**, make sure **SPF and DKIM** are valid for `brivia.tech`, and add a DMARC record (start with `p=none`).
- **Staff alerts** go to `BRIVIA_STAFF_NOTIFICATION_EMAILS`.
- **Controlled test:** submit the contact form with your **own** address and confirm both the acknowledgement and the staff alert arrive. Never test against real prospects.
- **After `.env` changes:** run `php artisan config:cache`, or simply deploy again.

## 7. Backups

| What | How | Retention (proposed, owner to approve) |
| --- | --- | --- |
| Database (automatic) | Each deploy writes `~/brivia-backups/brivia-db-…sql.gz` | Last 10 |
| Database (scheduled) | cPanel **Backup** (or JetBackup if offered) daily | 30 days |
| Images | cPanel Backup of the home directory, or download `/home/fitcbfra/brivia-app/storage/app/public` | 30 days |
| `.env` | Password manager (contains secrets) | Always current |

**Restore drill:** before launch and then quarterly, restore a backup into a **separate** database and check sign-in and content. Never restore into production as a test.

## 8. Security checklist before go-live

- [ ] `.env` has `APP_ENV=production` and `APP_DEBUG=false`, a unique `APP_KEY`, and mode `600`.
- [ ] HTTPS is active, *Force HTTPS Redirect* is on, and `SESSION_SECURE_COOKIE=true`. Enable `BRIVIA_HSTS=true` only once every subdomain serves HTTPS.
- [ ] The document root is `brivia-app/public`. `https://brivia.tech/.env`, `/storage/logs/laravel.log` and `/vendor/` must all **not** be reachable (404/403).
- [ ] The response headers include a CSP, `X-Frame-Options`, `nosniff`, `Referrer-Policy` and `Permissions-Policy`, and no `X-Powered-By`.
- [ ] Cron is running and the email test is done. The privacy and terms pages are owner-approved and published.
- [ ] The owner account exists with a strong, unique password. Two-factor authentication was removed at the owner's request, so consider restricting `/admin` (for example with an IP allowlist via cPanel *IP Blocker*/*.htaccess*) if feasible.
- [ ] **No demo content:** the production database never had the demo seeder run, and the `demo_records` table is empty.
- [ ] `composer audit` and `npm audit` are clean, or any advisories have been evaluated.

## 9. Dependency updates

Monthly, and whenever an advisory is published:
1. Run `composer audit` and `npm audit` locally.
2. Apply patch and minor updates on a branch, run `php artisan test` and `npm run build`, then merge to `main` and release as in §1.
3. Plan major upgrades separately. Never use `npm audit fix --force` blindly.

## What was verified locally, and what still needs the host

**Verified locally (Windows, Git Bash, PHP 8.3, MariaDB 10.4):**
- `actionlint` (with `shellcheck`) on the workflow and `shellcheck -S style` on the scripts: clean.
- YAML parsed.
- `package-frontend.sh` refused a dev build. It built an archive and checksum that verify, with `build-info.json` recording the SHA.
- `deploy.sh` ran end to end against a simulated clone (a real Git commit), file-based "releases" and a separate MariaDB database:
  - **Failure paths, all stopping before any change:** build not published, tampered checksum, path outside `build/`, wrong source SHA, missing `APP_KEY`, concurrent lock, non-BRIVIA target.
  - **Dry run:** no change and no backup.
  - **First deploy:** this found and fixed an ordering bug, where the database cache was cleared before migrations on an empty database. The recovery redeploy then succeeded.
  - **Later deploys:** a new commit over a live app (maintenance on, then off), and a repeat of the same commit (idempotent). After each, the health check passed and Home, Services, Contact and admin login returned 200.
  - **Preserved:** `.env`, the ACME challenge file, `.user.ini`, uploads, logs and the cPanel PHP handler block.
  - **Removed and excluded:** a stale code file was removed, and dev files and dev packages were excluded.

**Needs verification on Namecheap** ⚠:
- **rsync:** real `rsync` behaviour. Locally a test-only stand-in was used because no Linux `rsync` was available. Run the documented **dry run** first: it uses the server's real `rsync --dry-run --itemize-changes`.
- **Tools and paths:**
  - `flock`, `mysqldump` and `git` exist on the account (the script falls back to an `mkdir` lock without `flock`; it stops if `mysqldump` is missing).
  - That `/home/fitcbfra/bin/composer` is the Composer phar.
  - That cPanel runs `.cpanel.yml` tasks with bash available at `/bin/bash`.
- **Network:** outbound HTTPS to `github.com` and `objects.githubusercontent.com` from the server (release downloads).
- **PHP and Laravel:** PHP extensions, `symlink()` permission for `storage:link`, and that the cron job runs and the scheduled queue worker sends mail.
- **GitHub:** the first real workflow run (release creation with `GITHUB_TOKEN`, draft → publish, the tag pointing at the commit).
