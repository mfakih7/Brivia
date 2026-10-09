# BRIVIA — Implementation progress

## Current state
Specification bundle prepared on 2026-10-05. Implementation started the same day on a fresh Laravel 13 skeleton (no prior application code, empty SQLite database).

| Phase | Status | Owner approval |
| --- | --- | --- |
| Initial empty-project setup | Verified: Laravel 13.34.0 skeleton on PHP 8.3.29, SQLite, no data | Not recorded here |
| 1 — Foundation/security/schema | Implemented and checked (see report) | **Not reviewed by owner** |
| 2 — Content administration | Implemented and checked (see report) | **Not reviewed by owner** |
| 3 — Public website | Implemented and checked (see report); **stopped for owner review** | **Not reviewed by owner** |
| 4 — Enquiries/appointments | Implemented and checked (see report) | **Not reviewed by owner** |
| 5 — Final quality/preparation | Implemented and checked (see report); **stopped for owner review** | **Not reviewed by owner** |

## Baseline assumptions
Manual appointment confirmation; English launch; Blade/Tailwind; no public attachments, payments, client accounts or calendar integration; owner-editable content; packages in quote mode until real pricing is supplied. These are implementation defaults, not inferred owner approvals for later phases.

## Decisions/owner overrides

- **2026-10-05 — Workflow override (explicit owner instruction).** The owner instructed: *"Continue automatically to the next phase after the report. You do not need my approval between phases. This instruction explicitly overrides the earlier requirements in CLAUDE.md and the documentation to stop and request approval after every phase."*
  - Effect: phases 1–5 are implemented in sequence, with a report after each phase and no approval stop.
  - Affects: CLAUDE.md workflow steps 7–8, README "Five phases" and 06 shared rules 4–5.
  - **No phase is recorded as owner-reviewed or approved.** Pausing is still required when a decision or missing prerequisite genuinely prevents safe progress.
- **2026-10-05 — Second workflow instruction (explicit owner instruction, supersedes the automatic-continuation override).** The owner instructed: *"Continue and complete Phase 3 only … Stop and wait for my explicit instruction to resume. This overrides the earlier automatic continuation instruction. Do not begin Phase 4 or Phase 5."* Effect: Phase 3 was completed and work **stopped**. Phase 4 starts only after a new explicit owner instruction.
- **2026-10-09 — Published to GitHub (explicit owner instruction).**
  - **Pre-push review:** 40 files staged; no `.env`, keys, tokens, dumps, logs or SQLite files are tracked; the real local `APP_KEY` value appears in no tracked file; `.env.production.example` contains placeholders only.
  - **Remote:** origin set to `https://github.com/mfakih7/Brivia.git` (same repository; GitHub URLs are case-insensitive). Fetched with no divergence.
  - **Commit `0022e54`** "Prepare Brivia design refinements and Namecheap deployment" was pushed to `main` as a fast-forward (no force). The repository is public.
  - **First Frontend build failed at `npm ci`.** Reproduced locally: npm 10 (bundled with Node 22) rejects the npm 11 lockfile ("Missing: react@19.3.0 from lock file"), while npm 11 installs it cleanly for linux/x64. Fixed by building with Node 24 LTS (npm 11, `.nvmrc` = 24); a follow-up commit was pushed.
- **2026-10-09 — Namecheap deployment preparation (explicit owner instruction; prepared locally, nothing pushed, connected or deployed).**
  - **Added:**
    - `.github/workflows/frontend-build.yml`: runs on push to `main` and on manual runs; Node from `.nvmrc` (24 LTS with npm 11 to match the lockfile; Vite 8 needs's `^20.19.0 || >=22.12.0`); `npm ci` and `npm run build`; publishes `public/build` plus `build-info.json` and SHA-256 to a **draft** release, verifies, then publishes `build-<full SHA>` using only `GITHUB_TOKEN` (`contents: write` on that job). Not marked latest; reruns are safe.
    - `scripts/package-frontend.sh`.
    - `.cpanel.yml`, which calls `scripts/deploy.sh`.
    - `scripts/deploy.sh`: locked, exact-HEAD deploy; matching-SHA build only; checksum, archive-path and manifest verification; `git archive` staging; `composer install --no-dev` from the lock; pre-deploy `mysqldump`; maintenance mode; a protected `rsync --delete`; cPanel `.htaccess` handler preserved; `migrate --force`, `storage:link`, `optimize`, `queue:restart`; health check; dry-run mode.
    - `.env.production.example`, `.nvmrc`, and `.gitattributes` LF rules for deployment files.
    - The scheduler is aligned to 5-minute cron (dispatcher every 5 min; media cleanup at 03:10), with an opt-in bounded queue worker (`BRIVIA_SCHEDULER_QUEUE_WORKER`). New config `brivia.scheduler` and `tests/Feature/ScheduleTest.php`.
    - `DEPLOYMENT.md` rewritten for Namecheap. `OPERATIONS.md` and `SETUP.md` updated.
  - **Local verification:**
    - `actionlint` and `shellcheck` are clean, and the YAML parses.
    - Package script tested.
    - **Deploy script run end to end** against a simulated clone, file "releases" and a separate MariaDB database (`brivia_deploytest`, dropped afterwards):
      - 7 failure paths all stop before any change.
      - The dry run changes nothing.
      - First deploy, new-commit deploy and repeat deploy all succeed with health checks passing.
      - Protected files are preserved, stale code is removed, and dev files are excluded.
    - Two real bugs were found and fixed during the simulation: the database cache was cleared before migrations on a fresh database, and the failure summary was duplicated.
    - `rsync` was replaced locally by a test-only stand-in (no Linux rsync on this machine), so real rsync behaviour is listed for hosting verification together with the other ⚠ items in DEPLOYMENT.md.
  - **Repository note:** the project folder is now a Git repository (`main`, origin `github.com/mfakih7/brivia`, HEAD `0292f7b`). Nothing was committed or pushed in this step.
- **2026-10-09 — Design refinement: service cards, Services grid, contact tiles (explicit owner instruction).**
  - The shared `x-public.service-card` (icon tile, title, summary, always-visible "Explore service →") is used on the homepage (4 columns on desktop, 2 on tablet and phone) and the Services page (2 columns at every width, deliverables from 768px, a concise clamped summary on phones). The numbered `service-row` was removed.
  - A new `x-public.contact-tile` provides compact two-column contact tiles (email, phone, WhatsApp, consultation, full-width location). Only configured values render, values are escaped, and emails wrap after `@` and `.`.
  - `scroll-padding-top` was added for the sticky header. The owner mentioned attached screenshots, but none were received in the session; the work followed the written requirements.
  - **Checks:**
    - At 320, 375, 390, 768 and 1440px: correct column counts, 0px overflow, no clipped visible text, minimum visible card text 14px (12px only for the small tile labels), and the page heading never hidden by the sticky header (on load or at `#main`).
    - axe: 0 violations at 1440 and 320px.
    - New regression tests for shared cards and tiles.
    - Production build passes.
    - Screenshots are in `docs/screenshots/refinement/`.
- **2026-10-09 — Public website redesign and FAQ removal (explicit owner instruction; overrides the earlier mockup and the spec 01/02 homepage and FAQ requirements where they conflict).** The owner's reference screenshot (the previous homepage hero) was reviewed.
  - **Implemented:**
    - **Button system:** pill buttons with refined padding and typography in four sizes and variants (`btn`, `btn-lg`, `btn-sm`; primary, secondary, quiet, link), visible focus and arrow hover.
    - **Header:** solid sticky navy bar. Nav links carry a cyan active bar and `aria-current`, and a **compact "quiet" Book a consultation pill** behind a hairline divider replaces the large blue block.
    - **Mobile menu:** circular toggle with icon swap, 56 px rows, stacked actions.
    - **Hero:** typography-led with **the bridge illustration removed**. A large headline is followed by the subtitle and CTAs on the left and a numbered strengths list on the right, over a subtle navy gradient. No empty column remains.
    - **Every public page redesigned:** see `docs/DESIGN.md`. Includes the new `page-header`, `section-heading`, `service-row`, media-first `project-card`, pricing-column `package-card`, numbered process timeline, navy CTA band, footer, error pages, sectioned contact and consultation forms and the legal reading column.
    - **Responsive grid:** packages use a balanced 2×2 for even counts. Long words in headings and text wrap safely.
    - The **admin** shares the refined button, card, chip and form styles and was checked visually; FAQ is gone from its navigation and dashboard.
  - **FAQ removed everywhere:**
    - Public sections, the admin module (routes, controller, request, views, navigation item and dashboard count), the `Faq` model, the `FaqPlacement` enum, factory and seed data (draft and demo), the homepage FAQ heading and visibility option, the audit resource type, demo-cleanup references, tests and docs.
    - The new migration `2026_10_09_000200_remove_faq_feature` drops the `faqs` table and `homepage_content.faq_heading`, removes the `faq` key from the homepage visibility map, and deletes the demo-registry rows that pointed at FAQs. Applied migrations were not edited, the database was not reset, and historical audit entries are kept.
    - Verified on MySQL: the table and column are gone, and the users, services, projects and other demo data are unchanged.
  - **Checks run (2026-10-09, port 8090, MariaDB `brivia` with demo content):**
    - `php artisan test`: **138 passed, 1 skipped (the opt-in MySQL test), 935 assertions**. New tests: FAQ removed everywhere (schema, routes, public pages, admin) and a typography-led hero with no illustration. Updated tests: the FAQ assertion was replaced and the CTA copy changed to sentence case.
    - `vendor/bin/pint` passes. `npm run build` passes (CSS 67.4 kB / 12.9 kB gzip, JS 2.6 kB).
    - **Screenshots:** 10 page types at 1440, 768 and 390px before and after (`docs/screenshots/redesign/`), plus the open mobile menu. All after-captures have **0px horizontal overflow**.
    - **Overflow at 320px and 640px (200% zoom):** 0px on every public page. A long-content stress test (120-character unbroken words injected into every heading) initially overflowed by up to 262px; this was fixed with `overflow-wrap`, and all pages are now 0px.
    - **axe-core WCAG 2.0/2.1 AA including colour contrast:** 0 violations on 10 public pages at 1440px and 4 at 390px, and on 4 admin screens.
    - **Form error state:** submitting Contact and Consultation empty focuses the summary, and every invalid field has `aria-invalid` with a working `aria-describedby`.
    - **Keyboard, mobile menu:** Tab reaches the toggle; Enter opens it and the label changes to "Close menu"; Tab enters the links; Escape closes it and returns focus; the focus outline is visible.
    - **Admin regression:** dashboard, service edit, enquiries, homepage and login captured at 1440 and 390px with 0px overflow. This used a temporary copy database with a review-only account (`brivia_review`), which was **dropped afterwards**.
  - **Not done:** Lighthouse was not re-run after the redesign; the Phase 5 figures are pre-redesign. No screen-reader session was run. The legal pages are drafts in the local database, so they were verified by automated render tests rather than screenshots.
- **2026-10-09 — Two-factor authentication removed (explicit owner instruction; overrides the spec 03, 04 and 05 two-factor requirements).**
  - The owner instructed: *"Remove two-factor authentication from BRIVIA's admin login. I want staff to sign in using only their existing login email/username and password … This explicitly overrides the earlier specifications requiring two-factor authentication."*
  - **Removed:**
    - TOTP enrolment, challenge and recovery-code screens.
    - The two-factor middleware and redirects, and the recent-password confirmation that existed only to protect 2FA actions.
    - The `brivia:reset-two-factor` command and the 2FA chips in Admin → Staff.
    - The `pragmarx/google2fa` and `bacon/bacon-qr-code` packages.
    - 2FA wording in the invitation screen and email.
  - **Database:** a new migration, `2026_10_09_000100_remove_two_factor_columns_from_users_table`, drops `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` and `two_factor_last_used_step`. It does not edit applied migrations and does not reset anything, and it removes stored secrets that are no longer needed.
  - **Kept:** existing accounts and passwords, roles and permissions, active-account checks, password reset, sign-in throttling (5/min per email+IP, 20/min per IP), generic failure messages, CSRF, session regeneration on sign-in, invalidation on sign-out, session revocation on password change, reset or deactivation, final-owner protection, and invitations.
  - **Security note:** admin accounts are now protected by password only. Use strong unique passwords and consider network restrictions for `/admin` in production (see DEPLOYMENT.md).
  - **Verification:**
    - The owner account (id 1) was previously 2FA-enrolled. Its email and password-hash fingerprints, role and active status are **identical before and after** the migration on the `brivia` MySQL database.
    - `php artisan test`: **136 passed, 1 skipped, 919 assertions**. New tests:
      - direct sign-in to the dashboard with session regeneration
      - the intended page is restored after sign-in
      - role restrictions after password sign-in
      - the old 2FA URLs return 404
      - a previously enrolled account (legacy columns and data recreated, then the migration applied) keeps its row and signs in with its password
      - logout blocks admin pages
    - Existing tests still pass: generic invalid credentials, inactive account refused, throttling, password reset, CSRF 419, and the full role matrix.
    - The MySQL concurrency test passes on `brivia_test`. `composer audit` is clean, Pint passes, and on port 8090 `/admin/login` returns 200 and `/admin/two-factor/setup` returns 404.
- **2026-10-09 — Third workflow instruction (explicit owner instruction).** The owner instructed: *"Resume the BRIVIA project and complete Phases 4 and 5 … This explicitly authorizes proceeding from Phase 4 to Phase 5 without an approval checkpoint."* Effect: Phases 4 and 5 are implemented in sequence, followed by a stop for the owner's review. No phase is recorded as owner-approved.
- **2026-10-09 — Local environment after a restart.**
  - XAMPP MariaDB (port 3306, holding `brivia`) was not running. It was started with XAMPP's own command (`mysqld --defaults-file=mysql\bin\my.ini --standalone`), which is equivalent to the Control Panel Start button.
  - Data was intact: 1 user and 43 demo registry rows.
  - A separate `MySQL80` Windows service on port 3307 was found. It was **not** used or changed.
  - The site was restarted with `php artisan serve --host=127.0.0.1 --port=8090`.
- **2026-10-05 — Application database switched to local MySQL (explicit owner instruction).** The owner supplied the XAMPP MySQL settings (`DB_CONNECTION=mysql`, host `127.0.0.1:3306`, database `brivia`, user `root`, empty local password) and asked for the switch.
  - The server is **MariaDB 10.4.32** (XAMPP), not MySQL 8; production should still target MySQL 8.
  - Database `brivia` already existed and was **empty** (utf8mb4), so no data needed preserving.
  - Only the `DB_*` lines of `.env` were changed. A backup of the previous `.env` is kept outside the project in the session scratchpad.
  - Ran `config:clear`, `migrate:status` ("migration table not found"), then `migrate`: all 8 migrations ran. Then `db:seed`: idempotent singletons and draft starter content only.
  - No `migrate:fresh`, reset, rollback or table drops were used.
  - Result: 0 users. The old SQLite database also had 0 users, so there was no account to carry over.
  - `database/database.sqlite` is left untouched and is no longer used by the app. PHPUnit still uses in-memory SQLite (`phpunit.xml`), never the MySQL database.
- **2026-10-05 — Database (conflict raised, not silently resolved; superseded by the entry above for the local application database).** The specs target MySQL 8. The existing project `.env` uses SQLite, and the only local MySQL is XAMPP MariaDB 10.4.32, which was not running.
  - Credentials were not guessed and the application database was not switched.
  - Development and the PHPUnit suite run on SQLite.
  - Migrations avoid SQLite-only features and use InnoDB/utf8mb4-compatible types.
  - The MySQL row-locking concurrency test is opt-in and is reported as "not run" until a separate MySQL test database is configured.
- **2026-10-05 — Fonts.** The skeleton loaded Instrument Sans from the Bunny CDN. It was replaced with self-hosted Manrope and Inter (`@fontsource`, SIL OFL; licence files ship in `node_modules/@fontsource/*/LICENSE`) to meet the spec and a strict CSP.
- **2026-10-05 — Temporary logo assets.**
  - The header mark, favicon and apple-touch icon are **direct crops of the owner-supplied logo PNG** (the folded-B mark only, resampled, not traced or redrawn), shown beside a live-text "BRIVIA" wordmark.
  - The crop keeps the PNG's navy background, so it appears as a rounded tile.
  - **Asset blocker:** approved transparent lockup, mark-only and favicon files are still needed.
- **2026-10-05 — AGENTS.md** contains generic Laravel Boost / PHP 8.5 bootstrap text. Per CLAUDE.md it is ignored and has not been modified.
- **2026-10-05 — Rich text approach.** Long-form fields (service body, bios, legal, case-study narrative) are stored as structured plain text: paragraphs, `## ` headings and `- ` lists. They are rendered by `App\Support\StructuredText`, which escapes everything. No editor or visitor HTML is ever rendered, so no HTML sanitizer dependency is needed.
- **2026-10-05 — Placeholder protection.** Seeded founder cards and legal outlines carry `is_placeholder = true`.
  - Only an owner can clear the flag, which records owner acceptance.
  - Publishing placeholder content is blocked (enforced in Phase 2).
- **2026-10-05 — Consultation type pricing.** Seeded draft consultation types use "quote" pricing because no free/paid decision was supplied. The owner must confirm before publishing.
- **2026-10-05 — Enquiry choice lists.** Seeded budget and timeline choices are editable enquiry categories, not quotes. The owner should review the budget ranges.

## Installed versions/environment
- OS: Windows 11 Pro (development machine). PHP 8.3.29 NTS x64 at `C:\php83` (XAMPP's PHP 8.2 cannot run Laravel 13).
- Laravel 13.34.0, Composer 2, Node 24.12.0, npm 11.6.2, Vite 8, Tailwind CSS 4 (`@tailwindcss/vite`).
- Added PHP packages: `pragmarx/google2fa` ^8.0 (TOTP) and `bacon/bacon-qr-code` ^3.0 (inline SVG QR codes).
- Added npm packages: `@fontsource/inter` and `@fontsource/manrope`.
- PHP extensions present: bcmath, ctype, curl, fileinfo, gd (JPEG/PNG/WebP/AVIF), intl, mbstring, openssl, pdo_mysql, pdo_sqlite, zip and others.
- Database: SQLite (`database/database.sqlite`) for local development. MySQL 8 is not available locally; XAMPP MariaDB 10.4 is installed but was not running and was not used.
- No secret values are recorded here.

---

### Phase 1 — Foundation, schema and secure administration access
- **Date / status:** 2026-10-05. Implemented and checked. Not owner-reviewed.
- **Scope implemented:**
  - **Backed enums:** roles, publication, pricing, work origin, enquiry and appointment status (with transition rules), delivery status, notification kinds, legal keys and icon allowlist.
  - **Migrations** for every table in spec 03, plus `enquiry_events` for enquiry status history:
    - Staff columns on `users`.
    - Invitations, media and all content tables with their pivots.
    - Enquiries, appointments, notes, appointment events, audit log and notification outbox.
    - Unique slugs and references, decimal money columns, and restrictive foreign keys (cascade only on pivots, child media links, notes and events).
    - Publication, sort and status/date indexes.
  - **Models** with casts, relations and `published()` / `ordered()` scopes. Publication, role and status fields are not mass assignable. Append-only audit and appointment/enquiry event models. Notes must have exactly one parent. Content writes invalidate a versioned public cache.
  - **Factories** for all core models.
  - **Idempotent seeders:** singletons plus draft-only starter content. No users, passwords, prices or projects.
  - **Admin authentication** with custom Blade screens:
    - Sign-in uses generic failures and is limited to 5 attempts per minute per email and IP, with an IP ceiling of 20. The session is regenerated on sign-in, and inactive users are refused.
    - Sign-out invalidates the session.
    - Password reset has a 30-minute token, a generic response and a 3/min throttle, and revokes the user's sessions afterwards.
    - Recent-password confirmation lasts 15 minutes.
  - **Mandatory TOTP two-factor:**
    - The secret and recovery codes are encrypted at rest, codes cannot be replayed, and each of the 8 recovery codes works once.
    - Recovery codes are shown once.
    - Regenerating codes or resetting the authenticator requires recent password confirmation.
    - `brivia:reset-two-factor` is the console recovery path.
  - **Middleware:**
    - Every admin data route requires authenticated, active staff who have completed 2FA in the current session.
    - Deactivated accounts are signed out on their next request.
    - Admin responses are sent `no-store` and `noindex`.
  - **Staff primitives:**
    - Invitations are single-use, store only a SHA-256 token hash, expire after 48 hours and are queued by email. Reinviting revokes earlier invitations.
    - Acceptance sets the password, then 2FA enrolment follows.
    - Role changes and deactivation protect the final active owner and require reassignment of open appointments. Deactivation revokes sessions.
    - `brivia:create-owner` bootstraps the first owner interactively.
  - **Authorization:**
    - Gates: `manage-content`, `manage-operations`, `manage-staff`, `view-audit-log`, `delete-personal-records` and `approve-placeholder-content`.
    - The route groups are deny-by-default.
    - The sidebar shows only allowed modules.
  - **Role-scoped dashboard** with real counts. Content editors receive no client data.
  - **Protected placeholder routes** for modules due in later phases.
  - **Security headers** on every web response:
    - CSP: nonce scripts, self-hosted assets, `frame-ancestors 'none'`, and no `unsafe-inline` or `unsafe-eval` outside the Vite dev server.
    - nosniff, Referrer-Policy, Permissions-Policy, X-Frame-Options and COOP.
    - HSTS behind `BRIVIA_HSTS`.
    - A request ID is added to every response and audit entry.
  - **Privacy-safe `AuditLogger`:** resource-type allowlist; values kept only for operational fields; secrets dropped; personal and free-text fields recorded as `[changed]`.
  - **Frontend:** Tailwind v4 design tokens and component classes from spec 02, self-hosted fonts, and small vanilla JS modules (accessible nav toggle, form double-submit guard, error-summary focus, repeatable rows).
  - **Branded error pages:** 403, 404, 419, 429, 500 and 503. No stack traces.
  - `SETUP.md`.
- **Files/areas changed:**
  - Code: `app/Enums`, `app/Models` (and `Concerns`), `app/Support`, `app/Http/Controllers/Admin`, `app/Http/Middleware`, `app/Console/Commands`, `app/Mail`.
  - Config: `config/brivia.php`, `config/auth.php` (reset expiry 30 min, password timeout 15 min), `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`.
  - Routes: `routes/web.php`, `routes/admin.php`, `routes/admin-modules.php`.
  - Database: `database/migrations/2026_10_05_*`, `database/factories`, `database/seeders`.
  - Frontend: `resources/views/**`, `resources/css/app.css`, `resources/js/**`, `vite.config.js`, `public/images/brand/*`, `public/favicon.png` (the empty `favicon.ico` was removed).
  - Docs and environment: `.env.example`, `SETUP.md`, `tests/**`.
- **Decisions and specification deviations:** listed under "Decisions/owner overrides" above.
  - Two-factor cannot be "disabled" because it is mandatory. "Reset authenticator" clears enrolment and forces immediate re-enrolment, so the last owner always keeps a recovery path.
- **Checks actually run (all on Windows, PHP 8.3.29):**
  - `php artisan migrate` and `php artisan db:seed` on the local SQLite DB, which had no data: passed.
  - `php artisan test`: **47 tests, 251 assertions, all passed** (SQLite in memory). Coverage includes:
    - login, generic errors, throttle and inactive users
    - no registration route
    - 2FA enrolment, encryption at rest, replay rejection, single-use recovery codes, throttle and the password-confirm gate
    - reset generic response and session revocation
    - role access matrix across every module URL
    - dashboard data scoping and deactivation taking effect on the next request
    - final-owner protection
    - invitation hash, single use and expiry
    - owner command
    - publication scope, mass-assignment protection, relationships, restricted delete and audit redaction/append-only
    - structured-text escaping
    - security headers and CSRF 419
  - `vendor/bin/pint`: fixed 3 style issues, then clean.
  - `composer validate`: valid. `composer audit`: no advisories. `npm audit`: 0 vulnerabilities. `npm run build`: passed.
  - Live smoke test with `php artisan serve`: `/` 200, `/admin/login` 200 with CSP/noindex/no-store headers, `/admin` 302 to login, unknown URL 404.
- **Checks not run and why:** the MySQL migration run was not performed because no MySQL 8 is available locally.
- **Screenshots/manual review evidence:** admin login captured at 1440px with headless Chrome. It renders brand fonts, the cropped mark and visible focus styling. Full screenshot sets follow in Phases 3 and 5.
- **Owner inputs / blockers:**
  - Approved transparent logo assets.
  - A MySQL 8 database for production parity.
- **Local review steps:** follow `SETUP.md`, then:
  1. `php artisan brivia:create-owner`
  2. Sign in at `/admin/login` and enrol 2FA.
  3. Explore the dashboard and Security page.
- **Approval received:** none. Continuing per the owner's 2026-10-05 workflow override.
- **Next authorized action:** Phase 2 (content administration), per the workflow override.

### Phase 2 — Content administration and publishing
- **Date / status:** 2026-10-05. Implemented and checked. Not owner-reviewed.
- **Scope implemented:**
  - **Policy-protected admin modules** (deny-by-default gates on route groups):
    - Services, packages, projects (with categories), team, About (company profile), homepage, FAQs, consultation types, public settings and legal pages.
    - Staff management: invite, change role, deactivate, reactivate, revoke invitation.
    - Owner-only audit log viewer with filters.
  - **List screens:** server-side search, status/category filters and 20-per-page pagination with query strings.
    - Search uses bounded LIKE with an explicit `ESCAPE` that behaves the same on MySQL and SQLite.
    - Lists have stable ordering, status chips and empty states.
    - Reordering validates every ID inside a transaction.
  - **Forms:** Form Requests with strict limits and allowlisted enums, currencies, icons, CTA routes, timezones and social networks.
    - URLs are http(s) only; the WhatsApp link must be https on a WhatsApp host.
    - Repeatable rows are normalized (trimmed, blanks dropped, unknown keys dropped). They work without JavaScript, and JS adds add/remove.
    - Edits are preserved when validation fails.
    - Optimistic stale-edit protection uses the record's `updated_at`.
  - **Publication:** explicit Publish/Unpublish actions; saving fields never changes publication state. Publish checks and audits:
    - required copy
    - a price amount for fixed or starting-from pricing
    - project permission, cover image and image alt text
    - placeholder founders and legal pages are blocked in production until an owner approves them
    - concept projects can only be published by an owner in production
  - **Integrity:**
    - Published slugs are locked.
    - Quote mode forces a null amount.
    - Published legal text cannot change without a new version label.
    - A published project cannot drop its permission.
    - A published project's cover can only be replaced, not removed.
  - **Deletes:** referenced services, packages, projects, categories and consultation types cannot be deleted. The message explains the dependency and points to Unpublish.
  - **Images (`ImageProcessor`):**
    - Checks file size, extension and double-extension tricks.
    - Sniffs the real format with `getimagesize` and `finfo`, accepting only JPEG, PNG and WebP (no SVG or GIF).
    - Checks dimensions and pixel count **before** decoding, with a minimum size per role.
    - Raises memory to 512M only while processing.
    - Re-encodes to WebP at 480, 960 and 1600 widths (metadata is not carried over) with random server-generated paths.
    - Cover replacement is transactional; gallery is capped at 12 images; portraits need a "this is an approved photo" confirmation.
    - `brivia:media-cleanup` runs daily and deletes only unreferenced, database-known paths.
  - **Preview:** authenticated draft preview at `no-store` and `noindex`, with no shareable links. It uses a minimal renderer for now; Phase 3 switches it to the public templates.
  - **Cache invalidation:** content model events and pivot syncs bump the versioned public-content cache.
  - **Owner-only "Mark as owner-approved"** for placeholder founder and legal content, recorded in the audit log.
  - **`OPERATIONS.md`** with content-editing, images, honesty rules, placeholder and staff-access procedures.
- **Files/areas changed:**
  - Code: `app/Http/Controllers/Admin/*` (new controllers and `Concerns/`), `app/Http/Requests/Admin/*`, `app/Support/{ImageProcessor,PreviewRenderer}.php`, `app/Console/Commands/CleanupMedia.php`, `app/Models/LegalPage.php` (now uses the shared publication trait).
  - Routes: `routes/admin-modules.php` and `routes/console.php` (schedule).
  - Views: `resources/views/admin/**` and `resources/views/components/admin/*`.
  - Security and build: `app/Http/Middleware/SecurityHeaders.php` and `vite.config.js` (dev host).
  - Docs and tests: `OPERATIONS.md` and `tests/Feature/Admin/*`.
- **Decisions and specification deviations:**
  - V1 uses "Save draft" for drafts and "Save changes" for published items, with separate Publish/Unpublish. There are no revisions, as allowed by spec 04.
  - OG image upload per item is not exposed. The `og_media_id` column exists, project covers act as share images, and the site default is used otherwise.
  - **Local dev CSP:** the owner's running `npm run dev` served from `http://[::1]:5173`, and CSP cannot express IPv6 literals.
    - `vite.config.js` now pins `127.0.0.1`; this takes effect when the dev server is restarted.
    - Until then the CSP header is skipped **only** when `APP_ENV=local` and the hot URL is IPv6. Production is unaffected.
  - The development PHP has `upload_max_filesize=2M`. php.ini was **not** changed; the requirement is documented (≥ 6M).
- **Checks actually run:**
  - `php artisan test`: **81 tests, 452 assertions, all passed**. New coverage:
    - draft creation and list normalization
    - status injection ignored
    - publish requirements and audit
    - save does not change publication
    - slug lock and stale edit
    - referenced delete blocked
    - pricing modes, currency allowlist and price audit
    - cache invalidation
    - operations manager denied every content write and preview
    - preview auth, noindex, no-store and XSS escaping
    - reorder ID validation and wildcard-escaped search
    - unsafe URLs, WhatsApp host and unknown social keys rejected
    - CTA route allowlist and visibility map
    - every editor screen renders
    - WebP derivatives with random paths
    - replacement plus cleanup
    - PHP-in-JPG, fake image, SVG, GIF, too small, too many pixels and over 5 MB all rejected with no files written
    - gallery cap
    - project publish rules, cross-project media IDs and `javascript:` URLs rejected
    - permission lock and portrait approval
    - placeholder and legal production blocks, owner-only approval, legal version bump and honest draft policy version
    - staff invite, role change, deactivation and self-protection
    - invitation throttle (429)
    - non-owners denied staff management and the audit log
    - audit filter injection-safe
  - `vendor/bin/pint` (3 test files reformatted, then clean), `npm run build`, `php artisan schedule:list` (media cleanup daily).
  - **Browser review:** headless Chrome with `puppeteer-core`, signed in through the real login and 2FA flow against a **copy** of the local database with a review-only owner. The project database was not modified.
    - Captured dashboard, service edit, packages, homepage and staff at 390 and 1440px, and 11 admin screens at 320 and 390px.
    - Found and fixed a 124px horizontal overflow on phone tables (a visually hidden header label escaped the scroll region).
    - All captured screens now report 0px overflow.
- **Checks not run and why:** real image uploads through a browser were not run, because the development PHP limits uploads to 2M. Upload behaviour is covered by feature tests with GD-generated images.
- **Owner inputs / blockers:** none new. Real content still needs the owner's input: services, packages, prices, founders, legal text and settings.
- **Local review steps:**
  1. Sign in at `/admin`.
  2. Edit a seeded draft service or package, then use Preview, Publish and Unpublish.
  3. Under *Projects → Categories*, create a project, upload a cover (after raising `upload_max_filesize`) and publish it.
  4. Under *Staff*, invite a colleague; with `MAIL_MAILER=log` the link appears in `storage/logs/laravel.log`.
- **Approval received:** none. Continuing per the owner's 2026-10-05 workflow override.
- **Next authorized action:** Phase 3 (public website), per the workflow override.

### Phase 3 — Public website and responsive design
- **Date / status:** 2026-10-05. Implemented and checked. **Stopped for owner review**, per the owner's instruction. Not owner-reviewed.
- **Scope implemented:**
  - **All public GET pages from spec 01**, server-rendered from published database content:
    - Home: hero, credibility line, services, featured projects, package preview beside the About teaser on wide screens, delivery process, FAQ and final CTA.
    - Services overview and service detail, with "who it helps", typical deliverables marked as examples, engagement steps, and related published packages and projects.
    - Packages: comparison cards, accessible "Not included" disclosure, custom-scope note and packages FAQ.
    - Projects: category filter chips and pagination.
    - Case study: origin notice, cover, narrative sections, gallery, technologies, services, approved website link, related projects and "Discuss a similar project".
    - About: story, mission, founder cards with initials when no portrait, values and process.
    - Contact and Consultation (presentation only, see below), Privacy, Terms, and branded errors from Phase 1.
  - **Empty or hidden sections are omitted**, and empty pages explain what to do instead. There are no invented logos, metrics, testimonials, prices or client work.
  - **Projects listing:**
    - 9 per page, newest first (published date, then ID).
    - Category filter is server-side and accepts only categories that have published projects; anything else returns 404.
    - Query strings are kept on pagination; out-of-range pages return 404.
    - Each project carries an origin label: *Sample concept* or *Earlier founder experience*, with notices on the case study.
  - **Contact context preselection:**
    - `?service=`, `?package=` and `?project=` resolve **only published** records by slug.
    - Selecting a package states "This is not a purchase".
    - Fields match spec 01, including a honeypot field.
  - **Consultation form:** grouped IANA timezone select defaulting to the site setting and auto-detected from the browser when possible. Date limits cover 90 days and an optional alternate time is offered. The intro states that the time is not reserved until confirmed.
  - **Forms are visibly disabled in this phase.** A notice reads "not enabled in this review build" and the submit button is disabled. There is no POST route, so there is no false success.
  - **Navigation:**
    - Header with lockup, nav links and a "Book a Consultation" CTA.
    - The active page is marked by `aria-current` plus an underline or dot, not colour alone.
    - Mobile disclosure menu with `aria-expanded`, Escape to close with focus returned to the toggle, and the full menu shown when JavaScript is off.
    - Skip link, native `details` FAQ, visible focus styles and reduced motion.
  - **Hero artwork** is a decorative inline SVG approximation of the bridge, `aria-hidden`. **Asset blocker:** an approved, licensed hero image is still needed. The mockup is never used as an image.
  - **Images:** WebP `srcset`/`sizes` with explicit width and height. Only the case-study cover loads eagerly with high priority; the rest lazy-load.
  - **SEO:**
    - Unique titles and descriptions, canonical URLs, Open Graph and Twitter tags.
    - Organization JSON-LD built only from settings, with a CSP nonce, output only when indexable.
    - `/sitemap.xml` lists published routes and resources only.
    - `/robots.txt` disallows `/admin` in production and **everything outside production**.
    - Pages are `noindex` outside production.
    - The static `public/robots.txt` (skeleton default) was removed in favour of the route.
  - **Preview** (`PreviewRenderer`) now uses the real public templates with a "Private preview" banner.
  - **`LocalReviewSeeder`** was added for review at first; it was **later replaced by `DemoContentSeeder`** (see "Demo content for review" below).
  - `php artisan storage:link` was run so `public/storage` serves approved images.
- **Files/areas changed:**
  - Code: `app/Http/Controllers/Public/*`, `app/Support/{PublicSite,Timezones,PreviewRenderer}.php`.
  - Routes and seeders: `routes/web.php`, `database/seeders/LocalReviewSeeder.php`.
  - Views: `resources/views/components/layouts/public.blade.php`, `resources/views/components/public/*`, `resources/views/public/**`. The Phase 1 coming-soon page was removed.
  - Tests: `tests/Feature/Public/PublicSiteTest.php`, `tests/Feature/SecurityHeadersTest.php` (now uses a database).
- **Decisions and specification deviations:**
  - **No Eloquent objects in the cache.** Laravel 13 defaults `cache.serializable_classes` to `false`, which blocks object deserialization (a security hardening). Caching models produced `__PHP_Incomplete_Class` 500s during browser review, so that was reverted.
    - Public pages query published content directly: small, indexed queries with eager loading.
    - Only scalar data is cached (legal-link flags, sitemap XML), versioned by `PublicContentCache` and invalidated on every content write.
    - The hardening was kept rather than allow-listing classes.
  - **Gallery** uses linked full-size images (new tab) rather than a dialog viewer, as spec 02 allows.
  - **Package detail** expands inline (no detail route). "Request a quote" buttons go to Contact with the package preselected.
  - **Founder heading on About:** "Two senior developers. Over 10 years of experience each." This is the owner-approved positioning line from spec 01, not a claim about company age.
- **Checks actually run:**
  - `php artisan test`: **95 tests, 574 assertions, all passed**. New Phase 3 coverage:
    - every public page renders
    - drafts invisible everywhere; published content appears
    - empty and hidden sections omitted
    - unknown, uppercase and draft slugs return 404, and the branded 404 renders
    - draft project absent from list, detail and sitemap, with authorized-only preview (`noindex`)
    - newest-first ordering, category filter, 9-per-page pagination keeping query strings, invalid or empty category and out-of-range page all 404
    - origin labels and notices
    - case-study cover and gallery, with XSS in title and narrative escaped
    - packages show no invented prices
    - contact preselects only published context
    - forms clearly disabled (POST returns 405)
    - consultation shows the manual-confirmation copy, with a fallback when no types are published
    - legal pages and footer links follow publication
    - canonical, Open Graph and noindex outside production
    - sitemap content type and published-only entries
    - robots in both environments
    - JSON-LD in production
    - `aria-current` and nav toggle markup
  - `vendor/bin/pint --test`: pass. `npm run build`: pass (CSS 61.9 kB / 11.8 kB gzip, JS 2.6 kB).
  - **Browser review:** headless Chrome via `puppeteer-core`, against a **copy** of the local database seeded with `LocalReviewSeeder`. The owner's database was not modified.
    - Home, Services, Service detail, Packages, Projects, Case study, About, Contact, Consultation, Privacy and 404 captured at **390 / 768 / 1440px**.
    - All return 200 (404 for the missing page) with **0px horizontal overflow**.
    - The same 10 pages report **0px overflow at 320px**.
  - **Keyboard check (390px):**
    - The menu toggle is reachable by Tab and opens with Enter (`aria-expanded=true`, menu visible).
    - The next Tab moves into the menu links.
    - Escape closes the menu and returns focus to the toggle.
    - At desktop width the menu is always visible.
  - **Issues found and fixed during review:**
    - 500 errors from cached models (above).
    - Process steps fell back to 2 columns because the Tailwind class was built dynamically; now uses literal classes.
    - Cramped package cards beside the About teaser; the compact card layout was reworked and the column ratio widened.
  - **Smoke test on the owner's own server (port 8000, drafts only):** all public routes 200; `/privacy` 404 as expected for a draft.
- **Visual comparison with the reference mockup:**
  - **Matches the agreed direction:** navy hero with left copy and right bridge artwork; blue/cyan accents and dotted heading accents; four aligned service cards; two 16:10 project cards; package cards with "Request a quote" beside the About teaser on wide screens; dark CTA strip and compact footer; the phone layout stacks copy, CTAs and artwork in the mockup's order.
  - **Differences and why:**
    - The hero artwork is a simplified vector stand-in; an approved image is needed.
    - The header mark is cropped from the opaque logo PNG beside a text wordmark; approved transparent assets are needed.
    - Project screenshots are generated abstract sample covers, not the mockup's fictional screens, which spec 02 says must not be reused.
    - The homepage adds the process and FAQ sections the spec requires but the mockup omits.
    - Package cards show full readable content rather than the mockup's compressed cards.
    - Social icons appear only when configured; none are configured yet.
- **Checks not run and why:**
  - 200% browser zoom was not run as a separate zoom test. The 390 and 320px captures approximate the reflow, since 200% zoom on a 1280px window gives a 640px layout width.
  - Screen-reader testing was not run (no assistive technology available headless).
  - Lighthouse and performance measurement are deferred to Phase 5, per spec 06.
- **Owner inputs / blockers:**
  - Approved transparent logo lockup, mark and favicon, and an approved, licensed hero image.
  - Real published services and packages, decided prices or quote mode, real founder details and portraits, permitted case studies.
  - Approved privacy and terms text. Contact details and social links in Settings.
  - Budget-range wording review.
- **Local review steps:** the owner's own database contains drafts only, so the public site is mostly empty there by design. To review with sample content:
  1. Use a separate database copy, or accept that this publishes the starter drafts locally: `php artisan db:seed --class=LocalReviewSeeder`.
  2. Run `php artisan serve` and open `http://localhost:8000`.
  3. Check `/`, `/services`, `/services/new-products`, `/packages`, `/projects`, `/projects/pulse-dashboard`, `/about`, `/contact?package=business-website`, `/consultation`, `/privacy`, `/sitemap.xml` and `/robots.txt`.
  4. Resize to phone width and use the menu with the keyboard.
  5. Alternatively publish items one by one from `/admin`.
- **Re-verification after the MySQL switch (2026-10-05):**
  - `php artisan serve --host=127.0.0.1 --port=8090` against MariaDB 10.4.32:
    - `/`, `/services`, `/packages`, `/projects`, `/about`, `/contact`, `/consultation`, `/sitemap.xml`, `/robots.txt` and `/admin/login` all return 200.
    - `/admin` redirects (302) to sign-in.
  - `php artisan test`: 95/95 passed.
  - With drafts only and no accounts, the public site shows the hero, process, CTA and empty-state pages until content is published.
- **Demo content for review (2026-10-05, explicit owner request, within the Phase 3 review):**
  - **Added:**
    - `DemoContentSeeder` (`database/seeders/`), `DemoImageGenerator` (local GD illustrations, no text, no external URLs) and the `DemoRecord` model.
    - A `demo_records` migration (new and non-destructive; it creates one table).
    - The `brivia:demo-content:remove` cleanup command and `tests/Feature/DemoContentTest.php`.
  - **Removed:** the earlier untracked `LocalReviewSeeder`. It published the real starter drafts and had no cleanup, and it had only ever been run against the scratch review copy, never the owner's database.
  - **Guarantees:**
    - Runs only in `local` or `testing`; aborts elsewhere, even when called directly.
    - Not called by `DatabaseSeeder`.
    - Idempotent: the second run on MySQL created 0 items.
    - Never overwrites real content: slug collisions are skipped, and singleton fields are filled only when blank, with originals recorded.
    - Does not touch users, passwords or security settings. Sends no mail, queues no jobs and schedules nothing (verified with `Mail::fake` and `Queue::fake`).
  - **Cleanup:**
    - Removes only registered records, child-first.
    - Keeps (and reports) demo records referenced by real data, including demo services linked to real packages or projects.
    - Restores singleton fields only if they still hold the demo value.
    - Deletes demo images only when unreferenced.
  - **Configuration change:** the `public` disk URL in `config/filesystems.php` is now host-relative (`/storage`, overridable with `PUBLIC_STORAGE_URL`). Images therefore work on any local host or port, such as `127.0.0.1:8090` while `APP_URL` says `localhost:8000`. The Open Graph image is converted to an absolute URL in the layout.
  - **Run on the configured MySQL database** with `C:\php83\php.exe artisan db:seed --class=DemoContentSeeder`. It created 25 tracked items:
    - 4 services, 4 packages, 6 projects, 18 media, 2 founders, 5 FAQs and 2 consultation types
    - site settings fields (email, phone, WhatsApp, address, coverage, social) and About values
    - Starter drafts were untouched and the existing owner account was unchanged.
  - **Verified:**
    - `php artisan test`: **100 tests, 659 assertions, all passed**. 5 new tests cover population plus idempotency, no overwrite of real content, cleanup scope and restore, the environment guard, and `DatabaseSeeder` exclusion.
    - Pint passes.
    - Headless Chrome on `http://127.0.0.1:8090` captured Home, Services, Service detail, Packages, Projects, a concept case study, the placeholder case study, About, Contact and Consultation at 390, 768 and 1440px: all 200 with 0px overflow.
    - Visually checked: Home (services, featured projects, packages with illustrative prices next to the About teaser, process, FAQ, CTA, footer contact details), Projects (6 labelled cards, category chips) and About (placeholder founders, values, process).
  - **Remaining placeholders while demo content is installed:**
    - founder names and biographies
    - the earlier-experience project
    - all "Sample concept" projects and their abstract images
    - illustrative prices (Business Website from $2,500, MVP from $9,000, Consulting $150, Technical consultation $100)
    - sample contact details and social links (`example.com`, `brivia.example` and `555` numbers are reserved fictional values)
    - sample About values
    - legal pages are still unpublished drafts, so footer privacy and terms links stay hidden
    - hero artwork and logo assets
- **Approval received:** none. The owner instructed a stop after Phase 3.
- **Next authorized action:** **Wait for an explicit owner instruction.** When authorized, Phase 4 will:
  - Add `POST /contact` and `POST /consultation` with Form Requests, published-only context resolution, privacy-version acceptance, 5/min throttling, honeypot and timing checks, and session-bound submission keys.
  - Convert IANA local times to UTC (rejecting DST gaps and overlaps, past times and times beyond 90 days) and persist records transactionally with the outbox.
  - Build the enquiry and appointment admin: lists, details, notes, assignees and status transitions.
  - Confirm and reschedule with assignee row locking and overlap checks, plus optimistic locking.
  - Add queued mail through the outbox, the recovery dispatcher, 24-hour reminders with stale suppression, and visible delivery failures.
  - Add the opt-in real-MySQL concurrency test (needs a separate MySQL test database) and complete `OPERATIONS.md`.

### Phase 4 — Enquiries, consultation requests and operations
- **Date / status:** 2026-10-09. Implemented and checked. Not owner-reviewed. Continued straight to Phase 5 per the owner's instruction.
- **Scope implemented:**
  - **`POST /contact` and `POST /consultation`:**
    - Form Requests apply the spec 01 rules: trimmed name 2–120 characters, RFC email up to 254, international phone, company up to 160, message or summary 20–5000, privacy acceptance required.
    - Context is resolved **only to published records by slug**; hidden IDs are never trusted.
    - Budget and timeline must come from the owner-configured lists.
    - Records the privacy version and acceptance time. A draft policy is recorded as `draft:<version>`.
  - **Abuse and idempotency (`VisitorSubmission`, `FormTokens`):**
    - Each displayed form gets a random UUID bound to the session.
    - Same key + same payload returns the same acknowledgement. Same key + different payload returns a conflict.
    - Keys from another session or never issued are refused, and existing references are never revealed.
    - A unique `submission_key` covers double-submit races.
    - Honeypot field and a minimum fill time (3 s by default, `BRIVIA_FORM_MIN_SECONDS`).
    - 5 submissions per minute per IP, returned as a form error that keeps the input and gives the retry wait.
    - Random references (`BRV-…` / `BRC-…`), with no internal IDs in URLs.
    - Post-redirect-get with the exact success copy from spec 01.
  - **Times (`LocalTime`):**
    - IANA local time is converted to UTC through the timezone database. **Nonexistent (DST gap) and ambiguous (DST overlap) times are rejected** with guidance and never shifted.
    - Times must be in the future and within 90 days.
    - The alternate time is optional, must be different, and both of its fields are required together.
    - The type name and duration are snapshotted on the record.
  - **Transactional outbox (`NotificationOutbox`, `SendNotificationDelivery`, `OutboxMail`):**
    - Rows are written in the same transaction as the record and dispatched after commit; a unique `event_key` prevents duplicates.
    - The job re-checks state under a row lock and suppresses obsolete schedule-bound messages by `schedule_version`.
    - Up to 3 attempts with backoff, then **Failed**; errors are redacted (no addresses or secrets).
    - The `brivia:notifications:dispatch` scheduler runs every minute and sends due reminders and orphaned rows.
    - Delivery statuses: pending, sent, failed, suppressed.
  - **Messages:** visitor acknowledgements for enquiries and requests (the request one states "not reserved"), staff alerts (reference, type and admin link only, no message body), confirmation, reschedule (with reason), decline, cancellation, and **one reminder 24 h before**. No reminder is scheduled when confirming inside 24 h, and "Meeting details will follow" is used when there is no link.
  - **Scheduling (`AppointmentScheduler`):**
    - Confirm and reschedule run in a transaction: lock the appointment, check the optimistic `lock_version`, lock the assignee (and previous assignee) user rows **in ascending ID order**, then check for overlapping **confirmed** appointments using `existing.start < new.end AND existing.end > new.start`. Adjacent slots are allowed.
    - The assignee must be an active owner or operations manager.
    - Rescheduling requires a reason.
    - Decline from requested; cancel from requested or confirmed; complete only once the start time has passed.
    - Closed appointments cannot be reconfirmed.
    - Append-only events and audit entries for every change.
  - **Enquiry workflow (`EnquiryWorkflow`):** the spec 04 transition graph with explicit reopen, assignment, history and stale-edit protection.
  - **Admin:**
    - Enquiries list and detail: filters by status, type and "assigned to me"; escaped search; mailto and copy contact; context snapshot and consent; status buttons limited to allowed transitions; notes; history; deliveries.
    - Appointments list and detail: visitor and staff timezones shown side by side; confirm or reschedule form; decline and cancel with reason; complete; notes; history; deliveries.
    - **Email deliveries** screen with failed/pending filters and **Retry**.
    - The dashboard links to records and failed emails.
    - Owner-only hard delete of closed records, after typing the reference to confirm.
    - Expected conflicts and stale edits return to the form with a clear message.
  - `OPERATIONS.md` now covers enquiries, appointments, deliveries, deletion, the proposed retention policy (pending owner approval) and the MySQL test.
- **Files/areas changed:**
  - Supporting code: `app/Support/{LocalTime,FormTokens,PublicReference,NotificationOutbox,VisitorSubmission,AppointmentScheduler,EnquiryWorkflow}.php`, `app/Jobs/SendNotificationDelivery.php`, `app/Mail/OutboxMail.php`, `app/Exceptions/{SchedulingConflict,StaleRecord}.php`, `app/Console/Commands/DispatchPendingNotifications.php`.
  - Requests and controllers: `app/Http/Requests/Public/*`, `app/Http/Controllers/Public/{Contact,Consultation}Controller.php`, `app/Http/Controllers/Admin/{Enquiry,Appointment,Notification}Controller.php`.
  - Removed: `ModulePlaceholderController` and the placeholder view.
  - Routes and configuration: `routes/{web,admin-modules,console}.php`, `bootstrap/app.php` (conflict rendering), `config/brivia.php` (`forms`, `testing`), `app/Providers/AppServiceProvider.php` (form limiter response), `app/Support/AdminNavigation.php`.
  - Views: `resources/views/admin/{enquiries,appointments,notifications}/*`, `components/admin/{deliveries,notes}`, `mail/outbox`, and the admin dashboard.
  - Tests: `tests/Unit/LocalTimeTest.php`, `tests/Feature/Public/{EnquirySubmission,ConsultationRequest}Test.php`, `tests/Feature/Admin/{AppointmentOperations,EnquiryOperations}Test.php`, `tests/Feature/MySqlConcurrencyTest.php` and `tests/Support/confirm-appointment.php`.
- **Decisions and specification deviations:**
  - The honeypot and too-fast checks show a neutral "please review and submit again" error, not a fake success.
  - Rate-limited submissions return to the form with input kept, rather than a bare 429 page.
  - Staff alert emails contain no message body (privacy); staff read it in the admin.
  - "Sent" means accepted by the mail provider. The UI and `OPERATIONS.md` say so explicitly.
  - Because of the in-flight guard, a delivery being sent is not re-sent for 2 minutes and the dispatcher waits 5 minutes. Duplicate emails remain possible only if a provider accepts a message and then errors.
  - Retention durations are only *proposed*. No automatic deletion is implemented until the owner approves.
  - **Test-harness note:** with Laravel 13's JSON session serialization, calling `assertSessionHasErrors` before a follow-up request stops the error bag from rendering in that request. This was confirmed to be a test-only artifact (the same flow without the assertion renders errors), so the affected test verifies the re-rendered page instead.
- **Checks actually run (Windows, PHP 8.3.29):**
  - **Full suite:** `php artisan test` gives **135 passed, 1 skipped (the opt-in MySQL test), 911 assertions**, using SQLite in memory.
  - **Real-database concurrency:** `BRIVIA_TEST_MYSQL_DATABASE=brivia_test php artisan test --filter=MySqlConcurrencyTest` against **MariaDB 10.4.32** (XAMPP), separate database `brivia_test`: **passed**. Two OS processes confirm overlapping times for the same assignee, with a 1.5 s widened race window; exactly one is confirmed and one gets a conflict.
  - **Mutation check:** with the assignee `lockForUpdate()` temporarily removed, the same test **failed** ("confirmed | confirmed", a double booking). The lock was then restored and the test passed again. This shows the test detects the race.
  - **New coverage:**
    - DST conversion: Beirut summer and winter, New York; gaps and overlaps in Berlin and New York; invalid input
    - enquiry persistence, context, consent and outbox (2 deliveries sent through a fake mailer)
    - inline errors with preserved input
    - unpublished or invalid context rejected
    - duplicate submit (same reference, no new rows) and conflicting payload
    - foreign or unissued keys with no reference leak
    - honeypot, too-fast submission and rate limiting
    - provider failure: record kept, delivery pending then failed, errors redacted
    - CSRF 419
    - consultation stored as UTC plus timezone plus snapshot, with the "not reserved" email
    - DST rejection, past times, beyond 90 days, duplicate or partial alternate time, unpublished type
    - confirmation in UTC with the reminder at −24 h, and no reminder inside 24 h
    - overlap rejected; adjacent and different-assignee bookings allowed
    - stale lock version
    - reschedule needs a reason and supersedes the old reminder
    - cancel suppresses the reminder, and a stale job sends nothing
    - dispatcher sends the due reminder and recovers orphans
    - decline and complete rules, non-operational assignee rejected, DST-ambiguous staff time rejected
    - escaped visitor text in admin
    - content editors forbidden
    - owner-only deletion of closed records only
    - enquiry transition graph and reopen, stale edit, assignment, notes, escaping, failed delivery visible and retryable without changing the enquiry
  - `vendor/bin/pint`: 2 files reformatted, then clean.
  - **End-to-end demonstration with local test data:** headless Chrome against a separate **copy** database `brivia_review` (dumped from `brivia`), with a review-only owner in that copy only, `MAIL_MAILER=log`, the real database queue and `queue:work`.
    - The visitor requested a consultation (Europe/London) and got "…not reserved until BRIVIA confirms it" with reference `BRC-…`. They also sent an enquiry with package context and got the spec success copy with reference `BRV-…`.
    - Four emails were written to the log: two acknowledgements and two staff alerts.
    - Staff signed in with password and 2FA, then confirmed. The email showed "Wednesday 14 October 2026, 10:00 (Europe/London, UTC+01:00)" plus the meeting link, and a reminder was scheduled.
    - Staff rescheduled with a reason. The old reminder was suppressed and a new one scheduled.
    - Staff cancelled with a reason. The new reminder was suppressed.
    - Final state: appointment `cancelled`, schedule version 3, 3 appended events, and 9 delivery rows (7 sent, 2 suppressed).
    - **The owner's `brivia` database and accounts were not modified.**
- **Checks not run and why:**
  - No real email delivery. Log and fake mailers only, per instruction; this needs a configured provider and controlled delivery testing before launch.
  - Concurrency was verified on MariaDB 10.4, not MySQL 8, because MySQL 8 is not the configured server. The InnoDB row-locking semantics used are the same.
- **Owner inputs / blockers:**
  - Staff notification recipients (`BRIVIA_STAFF_NOTIFICATION_EMAILS`).
  - Mail provider and sender domain.
  - Approval of the retention policy and the published privacy text, so consent stops reading `draft:`.
  - Staff timezone, if it is not Asia/Beirut.
- **Approval received:** none. Continuing to Phase 5 per the owner's 2026-10-09 instruction.

### Phase 5 — Integrated quality, hardening and launch preparation
- **Date / status:** 2026-10-09. Implemented and checked. **Stopped for the owner's final review.** Not deployed.
- **Fixes made during the review:**
  - **Security:**
    - `X-Powered-By: PHP/x.y` is now removed from every response. `expose_php=Off` is also documented.
    - **Host-header trust** is enabled (`trustHosts()`): outside local and testing, requests whose Host is not `APP_URL`'s host (or a subdomain) get a 400.
  - **Dependencies:** `npm audit` reported a **new critical advisory** (GHSA-pqg4-j6r4-53mv in `shell-quote`, reached through the dev-only `concurrently` from the Laravel skeleton; not part of the built site).
    - Fixed with a non-forced, in-range update: `shell-quote` 1.12.0 and `concurrently` 10.0.6.
    - `npm audit` now reports 0 vulnerabilities and `composer audit` reports no advisories.
  - **Accessibility:** axe-core flagged duplicate unnamed `<aside>` landmarks on admin detail and edit pages. The admin sidebar is now labelled, and the scan is clean.
  - **Performance:** `/projects` CLS was 0.104, caused by web-font swap reflowing cards. The above-the-fold fonts are now preloaded from the Vite manifest (Inter 400, Manrope 700/800), and **CLS is 0 on every measured page**.
  - **Usability:** on phones the enquiry contact, status and assignment panel is shown before the message.
  - **Licensing:** self-hosted font licence notices (SIL OFL 1.1) are published at `/licenses/fonts.txt`.
  - **Tests:** the MySQL concurrency test now always cleans up its rows (`finally`), and leftovers from the earlier mutation run were removed from `brivia_test`.
  - **Coverage added:** spec 04 acceptance scenario 1 end to end (publish, price change, public update, unpublish); a strict built-asset CSP with no PHP version leak; untrusted Host rejected; 500 page without details when debug is off.
- **Documentation finalised:**
  - `DEPLOYMENT.md` (new, host-neutral, **not tested on a host**): requirements and `php.ini`, the production `.env` without secrets, a release procedure without destructive commands, the queue worker (Supervisor) and scheduler (cron), backups and restore drill, rollback, a go-live security checklist, and dependency-update policy.
  - `SETUP.md`: XAMPP MariaDB start, upload limits, OPCache, port 8090 commands, queue/scheduler behaviour, and the MySQL test command.
  - `OPERATIONS.md`: maintenance ownership table and local demo content.
- **Integrated review against spec 01–05:**
  - Public pages use published data only.
  - Admin authorization is server-side for every route, with role tests.
  - Forms persist and show honest outcomes.
  - Notifications use the outbox and are visible.
  - Scheduling is lock-serialised, verified on a real database.
  - SEO hides drafts and admin; non-production is `noindex`.
  - Documentation is complete.
  - Remaining gaps are listed under "Remaining production inputs" and the open limitations below.
- **Checks actually run (2026-10-09, Windows 11, PHP 8.3.29, MariaDB 10.4.32, Chrome headless):**
  - `php artisan test`: **139 passed, 1 skipped, 933 assertions** (in-memory SQLite). The skip is the opt-in MySQL test.
  - `BRIVIA_TEST_MYSQL_DATABASE=brivia_test php artisan test --filter=MySqlConcurrencyTest`: **passed** on MariaDB 10.4.32. The Phase 4 mutation check showed it fails if the lock is removed.
  - `vendor/bin/pint --test` passes. `composer validate` and `composer audit` are clean. `npm audit` reports 0 vulnerabilities. `npm run build` passes (CSS 62 kB / 11.8 kB gzip, JS 2.6 kB, no source maps).
  - `php artisan optimize` builds the config, route, view and event caches successfully; they were then cleared (`optimize:clear`) to keep local development live. `schedule:list` shows the dispatcher every minute and media cleanup daily.
  - **Strict CSP in a browser:** with built assets (no Vite dev server) the CSP is `script-src 'self' 'nonce-…'` with no `unsafe-inline` or `unsafe-eval`, and Chrome reports **no console or CSP errors** on Home, a case study and Contact.
  - **Accessibility, axe-core 4 (WCAG 2.0/2.1 A and AA plus best practice):** 0 violations on 10 public pages at 1280px and 4 at 390px. 0 violations on 17 admin screens at 1280px and 5 at 390px, after the landmark fix.
  - **Error states:** submitting the Contact and Consultation forms empty at 390px moves focus to the error summary. Every invalid field has `aria-invalid` and a working `aria-describedby`, axe reports 0 violations and there is no overflow.
  - **Lighthouse 12, mobile profile with simulated throttling, local PHP dev server without OPcache, demo content:**

    | Page | Performance | Accessibility | Best practices | SEO | LCP | CLS | TBT |
    | --- | --- | --- | --- | --- | --- | --- | --- |
    | Home | 90 | 100 | 100 | 69 | 2.8 s | 0 | 0 ms |
    | Projects | 91 | 100 | 100 | 69 | 2.8 s | 0 | 0 ms |
    | Services | 95 | 100 | 100 | 69 | 2.6 s | 0 | 0 ms |
    | About | 97 | 100 | 100 | 69 | 2.4 s | 0 | 0 ms |

    Earlier runs gave Case study 95/100/100/69 (LCP 2.5 s) and Contact 96/100/100/69 (LCP 2.3 s), both before the font preload.

    **The SEO score of 69 comes only from `is-crawlable`**, which is the intentional `noindex` outside production. **LCP is 2.4–2.8 s, at or slightly above the 2.5 s goal.** Server time on this dev server is 0.3–0.5 s even for `robots.txt` (framework boot without OPcache), so production with OPcache should be lower. This has **not** been measured on a production host.
  - **Responsive:** headless Chrome reports **0px horizontal overflow** at 320px and at 640px (the layout width of 200% zoom on a 1280px window) for 8 public and 7 admin screens. Earlier phases covered 390, 768 and 1440px.
  - **Live site** on `http://127.0.0.1:8090` (owner's `brivia` database): `/` and `/admin/login` return 200. No `X-Powered-By` header.
- **Review environment and cleanup:**
  - Browser, accessibility and performance checks used a temporary copy database `brivia_review` with a review-only owner account, served on ports 8001 and 8002. Port 8002 used a scratch public folder without the Vite `hot` file, so built assets and the strict CSP were tested while the owner's `npm run dev` kept running.
  - The servers were stopped and **`brivia_review` was dropped** afterwards.
  - `brivia_test` remains, **empty**, for the MySQL test.
  - The owner's `brivia` database, accounts and demo content were not modified by Phase 4 or 5 checks.
- **Checks not run and why:**
  - Deployment to a real host, and HTTPS/HSTS behaviour: no host was chosen and deployment was not authorised.
  - Real email delivery: log and fake mailers only, per instruction.
  - Screen-reader testing with NVDA or VoiceOver: not available headless. Automated axe checks and keyboard checks were done instead.
  - Lighthouse on production infrastructure, and real-user metrics: require a live deployment.
  - Concurrency on MySQL 8: verified on MariaDB 10.4 (InnoDB) instead.
- **Open limitations, known and accepted for review:**
  - Public pages query the database on each request (small indexed queries). Only scalar fragments are cached, because Laravel 13 blocks object cache deserialization. Page or HTTP caching can be added on the host if needed.
  - There is no per-item Open Graph image upload; project covers and the site mark are used.
  - Gallery images open full size in a new tab rather than a lightbox.
  - The hero artwork and header logo are temporary.
- **Approval received:** none. **Stopped for the owner's review**, per the 2026-10-09 instruction.

## Phase report template
### Phase N — [name]
- Date / status:
- Scope implemented:
- Files/areas changed:
- Decisions and specification deviations:
- Checks actually run (command/environment/result):
- Checks not run and why:
- Screenshots/manual review evidence:
- Owner inputs / blockers:
- Local review steps:
- Approval received (actual message/date):
- Next authorized action:

## Remaining production inputs
Founder identity, bios and photos; actual contact details and notification recipients; authorized case studies; package pricing and scope; consultation types, duration and free/paid decision; privacy and terms approval; domain and host; mail provider; backup and retention approval. Keep placeholders in drafts. Do not fabricate production values.

**Launch blockers as of 2026-10-09** (none are passed or concealed):
1. **Brand assets:** approved transparent logo lockup, mark and favicon, and a licensed hero image.
2. **Content:** real services, packages and prices (or quote mode), founder names, bios and approved portraits, permitted case studies, About values, FAQs, consultation types and their free/paid decision. Remove all demo content first (`brivia:demo-content:remove`).
3. **Legal:** owner-approved privacy and terms text. Publishing them also stops consent records reading `draft:`.
4. **Retention:** approval of the proposed retention periods (12 months for closed enquiries and finished appointments, 12 months for audit metadata, 90 days for delivery metadata, 30 days for backups), and matching privacy text.
5. **Email:** mail provider and sender domain (SPF/DKIM/DMARC), staff recipient addresses, and a controlled delivery test.
6. **Hosting:** domain, a host supporting PHP 8.3 with MySQL 8, HTTPS, the queue worker, cron, encrypted backups with a tested restore, and the production `.env`. Follow DEPLOYMENT.md and record the verified procedure.
7. **Owner account:** create the first production owner (`brivia:create-owner`) with 2FA, and store the recovery codes.
8. **Owner authorisation to deploy**, given separately.
