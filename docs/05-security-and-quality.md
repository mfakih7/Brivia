# 05 — Security, quality and deployment acceptance

## Target
Risk-based controls for a public marketing site containing private prospect information and privileged staff administration. Do not label the product “fully secure” or claim compliance certification. Security includes implementation, configuration and ongoing operations.

## Required controls
| Area | Requirement | Evidence |
| --- | --- | --- |
| Authentication | Laravel password hashing, rate limits, generic failures, active staff, 2FA, no public registration | Login/reset/challenge tests |
| Authorization | Policies on resources/actions/uploads; role-scoped dashboards; deny by default | Allowed and denied role tests; direct URL attempts |
| Sessions | Regenerate on login; invalidate logout/reset; HttpOnly, SameSite=Lax, Secure on HTTPS; idle expiry and recent-password protection | Session tests/config review |
| CSRF | All browser mutations use Laravel CSRF; no wildcard exemptions | Missing/invalid token rejection |
| Validation | Form Requests, allowed enums/IDs, bounded text/lists, publication invariants | Invalid input tests |
| XSS | Blade escaping; server sanitization for limited rich content; safe URLs | Stored content/client-message malicious payload tests |
| SQL injection | Eloquent/bound queries, allowlisted sort/filter values; no raw visitor interpolation | Query/code review and hostile inputs |
| Uploads | Admin-only, MIME/decode/dimension/size checks, random names, re-encoded raster, no executable formats | Valid/invalid upload and unauthorized tests |
| Abuse | Rate-limit forms/invites/reset/login; honeypot and submit timing; configurable provider challenge only if later agreed | Throttle tests and accessible error |
| Personal data | Private DB records, limited logs, consent version, restricted notes and retention controls | Role tests/log review |
| Integrity | Transactions, unique idempotency keys, locking/concurrency for scheduling | Retry and simultaneous write tests |
| Runtime | Secrets in env, debug off, HTTPS, public document root, least-privilege DB/files | Production configuration checklist |

Defaults: login 5 attempts/minute by normalized email+IP and an additional IP ceiling; public forms 5/minute per IP with reasonable shared-network behavior; invitations/reset 3/minute per actor/IP as applicable. Return useful retry-after feedback. Honeypot/timing are supplementary, not sole security. No external challenge provider until privacy/accessibility/settings are agreed.

Image uploads: JPEG/PNG/WebP only, max 5MB/file, max 6000×6000 and 25 million decoded pixels (both limits), enforce minimum meaningful dimensions per role; max 12 gallery images/project. Verify decoded format, strip metadata, reject SVG/GIF/HTML/PHP or double-extension tricks. Reject oversized pixel count before expensive decode where possible; resource-limit processing. Generated image derivatives use safe backend library compatible with host PHP extensions. Browser accept attribute is merely a hint.

## HTTP and infrastructure
HTTPS redirects, HSTS only after host/subdomain HTTPS readiness, nosniff, suitable Referrer-Policy, Permissions-Policy, and CSP including frame-ancestors. Self-host fonts/scripts; establish explicit CSP rather than broad unsafe-inline/eval. Tune style/script policy to built output; report any exception. Prevent indexing admin, previews and private pages; robots is not access control. Correct trusted proxies/hosts so redirects, signed URLs and Secure cookies work without accepting arbitrary Host headers.

Production APP_DEBUG=false; keep .env/storage/logs/vendor/git files outside public routes; no writable executable uploads. Serve only public/. Rotate APP_KEY only with a migration/re-encryption plan, never casually on deployment. Backups include database and approved media; encrypt/restrict them and exercise restore. Do not commit .env/credentials/data dumps or expose browser sourcemaps unless deliberately approved.

## Meaningful test matrix
| Feature | Required checks |
| --- | --- |
| Public content | Draft 404, published visible, hidden related content omitted, filters/pagination stable, unknown route 404 |
| Admin | Every role permissions, inactive user, unauthenticated and pre-2FA access blocked, no final-owner removal |
| Content | Required publication data, invalid price mode, malformed JSON/URLs, media replacement/reference deletion |
| Enquiry | Valid persistence, field errors, invalid context, missing consent, duplicate submit, unavailable email provider |
| Appointment | UTC conversion, DST invalid/ambiguous times, past/out-of-range times, status transitions, conflicting schedule |
| Concurrency | Real MySQL overlap confirmation under concurrent transactions; do not rely solely on SQLite semantics |
| Notifications | Outbox recovery, retry without duplicate records, stale reminder suppression, failures visible |
| Security | CSRF, XSS, injection-safe filters, forbidden file types/size/pixels, direct unauthorized writes |
| UX | Keyboard nav/forms, error focus, mobile navigation, reduced motion, 200% zoom, no 320px overflow |

Use isolated test database, never the owner's application database. Keep test evidence accurate: unavailable MySQL/email/browser tools are “not run,” not passed. PHP syntax/Pint, composer validate/audit, npm audit and production build; evaluate advisories rather than ignoring them or blindly upgrading major versions.

## Performance and SEO
- Server-render page content with unique title/description/canonical URLs. Public sitemap includes only published routes/resources; robots excludes admin/preview. Prevent draft/legal placeholder indexing.
- Organization structured data only from approved factual settings; no invented ratings/reviews. Open Graph uses appropriate approved image. No canonical localhost in production.
- Eager-load related records where needed and paginate; avoid N+1; cache published content with explicit invalidation on mutations. Do not cache enquiry/admin pages publicly.
- Optimize media with widths/srcset; lazy-load below fold; preload only necessary font/hero assets. Minimize JavaScript and external widgets.
- Targets: LCP ≤2.5s, INP ≤200ms, CLS ≤0.1 under representative conditions; lab Lighthouse mobile performance ≥90 and accessibility ≥95 are goals, not unconditional guarantees. Document environment/device and results. Real-user metrics require post-launch observation.

## Visual review
Capture homepage 390/768/1440px; key inner-page types and admin forms phone/desktop. Check navigation, multiline long titles, empty content, 422 validation, loading/error/success, keyboard focus, images and scroll. Compare to reference. Do not write layout tests that merely restate CSS; use practical screenshots/browser checks and necessary interaction tests.

## Required project documentation
SETUP.md: exact prerequisites/versions, Windows/XAMPP compatibility, local DB config, first-owner bootstrap, build, queue, scheduler, tests and troubleshooting.
DEPLOYMENT.md: chosen host or host-neutral recipe until selected; release build, env, backups, migrations, optimize caches, queue restart, scheduler, storage/public permissions, health verification, rollback compatibility and previous build restoration. No migrate:fresh; no production npm dev/artisan serve.
OPERATIONS.md: content editing, enquiry triage, appointment confirmation/reschedule, failed notifications, staff access recovery, retention decision, backup restore and dependency update ownership.

## Launch checklist
- Owner approves real content, portfolio permissions and legal text; correct contact recipients/domain.
- Hosting supports PHP 8.3/Laravel13/dependency extensions; MySQL available; queue/scheduler running.
- Mail transport configured and controlled delivery tested; notifications not sent to fictitious demo addresses.
- HTTPS, debug off, sessions, headers, writable paths, cron, backups and restore checked.
- Auth/authorization/workflow checks pass; material unresolved risk listed.
- Data migration and rollback reviewed; no production reset command.
- Owner authorizes deployment separately. Phase 5 prepares launch; it does not publish.
