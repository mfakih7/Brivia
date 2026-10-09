# 03 — Architecture and database

## Stack and implementation style
Laravel 13 / PHP 8.3; Composer 2; Blade components; Tailwind CSS 4/Vite; vanilla JavaScript modules; MySQL 8/InnoDB/utf8mb4. Check Composer and npm engine constraints, pin exact installed versions in lockfiles. Use database queue/cache/session drivers initially; Redis optional later. PHPUnit feature/unit tests, Laravel Pint; add static analysis if compatible and useful. Avoid an unnecessary repository layer or generic CRUD framework.

Use thin controllers, Form Requests, Eloquent models/scopes, policies, explicit actions for transitions, and transactions for related changes. Services only for cohesive reusable concerns (timezone conversion, image processing, notification handling). Native backed enums for statuses/pricing/roles. Use server-side rendering; small fetch enhancements must retain a normal form fallback where practical.

Suggested code areas: app/Http/Controllers/Public, app/Http/Controllers/Admin, app/Http/Requests, app/Policies, app/Actions, app/Enums, app/Mail, app/Jobs. views/layouts, components, public and admin. Central CSS tokens and small component classes; no giant inline scripts or duplicated page markup.

## Authentication
Dedicated staff users table using Laravel authentication provider; no client users. Established Laravel authentication/two-factor primitives such as compatible Fortify backend are acceptable without installing Livewire/Filament views. Use custom Blade screens. Server-side auth, active-account check and completed two-factor challenge are required before admin data access. Password reset and invitations share tested broker primitives where possible.

## Schema conventions
All domain tables use bigint primary keys, created_at/updated_at. UTC datetimes; currency values use decimal(12,2), never float. Slugs unique per resource, normalized, stable after publication. Foreign keys restrict destructive deletes unless cascading child deletion is explicitly stated. JSON fields must have defined allowed shapes, not arbitrary blobs. No public mass-assignment of status/role/ownership/publication fields.

The following describes minimum business fields; framework queue/cache/session/password-reset tables are also required.

| Table | Fields and constraints |
| --- | --- |
| users | name(120), unique email(254), password hash, role(owner/content_editor/operations_manager), is_active, email_verified_at, encrypted two_factor_secret/recovery_codes, two_factor_confirmed_at, remember_token |
| admin_invitations | unique random token hash, email, role, invited_by FK, expires_at, accepted_at; single-use; no plain token storage |
| services | title(160), unique slug(180), summary(400), body structured/sanitized, deliverables JSON string list, engagement_steps JSON list, icon_key allowlist, sort_order, status(draft/published), published_at, SEO fields |
| packages | name(160), unique slug(180), summary(400), features JSON string list, exclusions JSON list, price_mode(quote/starting_from/fixed), price_amount nullable, currency char(3), billing_label nullable, is_featured, sort_order, status, published_at, SEO fields |
| package_service | package_id + service_id unique pair; cascade pivot on deletion |
| project_categories | name(80), unique slug(100), sort_order |
| projects | title(180), unique slug(200), category_id FK, summary(500), client_display_name nullable, work_origin(brivia/founder_experience/concept), contribution text, problem/approach/solution/outcomes structured text, technologies JSON string list, website_url nullable, is_featured, sort_order, status, published_at, permission_confirmed boolean, SEO fields |
| project_service | project_id + service_id unique pair; cascade pivot |
| media | disk, unique storage_path, mime_type, size_bytes, width, height, original_name, alt_text(240), uploaded_by FK; paths generated server-side |
| project_media | project_id FK, media_id FK, role(cover/gallery), caption nullable, sort_order; unique pair; exactly one cover enforced by transactional replacement |
| team_members | name(120), role_title(160), biography structured text, skills JSON list, years_experience unsigned integer, portrait_media_id nullable FK, social_links validated JSON map, sort_order, status |
| company_profile | singleton id=1; story, mission, values JSON list, public_intro; no arbitrary multiple companies |
| homepage_content | singleton id=1; hero_eyebrow/headline/subtitle, primary/secondary CTA label and allowed named route, about_heading, final_cta_heading/body, process_steps JSON objects(title,body), section visibility map |
| faqs | question(240), answer plain/sanitized text, placement(home/packages/both), sort_order, status |
| site_settings | singleton id=1; public business name, email, phone, address/service coverage, WhatsApp HTTPS URL, validated social URLs, budget_options/timeline_options JSON lists, default SEO title/description, default_timezone=Asia/Beirut; no runtime secrets |
| legal_pages | unique key(privacy/terms), title, body sanitized, version_label, status, published_at; preserve accepted-policy version independently of future edits |
| consultation_types | name(120), unique slug, description(500), duration_minutes, pricing_mode(free/quote/fixed), amount nullable, currency, sort_order, status |
| enquiries | unique public_reference (random), unique submission_key UUID, name/email/phone/company, enquiry_type, nullable service/package/project FK, context_snapshot JSON display titles, budget/timeline nullable strings, message text, status(new/in_review/contacted/qualified/closed/spam), assigned_to nullable users FK, policy_version, privacy_accepted_at |
| appointments | unique public_reference, unique submission_key UUID, consultation_type_id FK, type_snapshot/duration snapshot, contact fields, summary text, preferred_at_utc, alternate_at_utc nullable, requested_timezone IANA, confirmed_start_at_utc nullable, confirmed_end_at_utc nullable, status(requested/confirmed/completed/cancelled/declined), assigned_to nullable users FK, meeting_url nullable, cancellation_reason nullable, policy_version, privacy_accepted_at |
| record_notes | enquiry_id OR appointment_id nullable FK, author_id FK, body text(5000); exactly one parent; cascade on approved hard deletion |
| appointment_events | appointment_id FK, actor_id nullable FK, event_type, previous/new schedule/status JSON limited fields, reason nullable; append-only |
| audit_logs | actor_id nullable FK, action, resource_type allowlist, resource_id, changed_fields redacted JSON, request_id; append-only; no passwords/tokens/raw enquiry message |
| notification_deliveries | unique event_key, enquiry_id OR appointment_id nullable FK, kind, recipient_email, payload minimal JSON, status(pending/sent/failed), attempts, sent_at, last_error redacted; queue outbox |

SEO fields: meta_title ≤70, meta_description ≤160, optional og_media_id FK. Add indexes on publication status+published_at, sort_order, enquiry status+created_at, appointment status+confirmed_start_at_utc, assigned_to and search email. Search may use escaped LIKE with bounded results initially. Public query scopes always filter published records and related resources.

## Integrity and publication
- Price amount required/nonnegative only for fixed/starting_from; quote mode forces null. Currency must be allowlisted supported ISO currency, default USD. Free consultations have null amount and explicit Free label.
- JSON lists have bounded counts/lengths; process list objects contain title/body only. Validate URLs as http/https (WhatsApp https); deny javascript/data URLs.
- Publish action validates required copy, selected media alt text and project permission confirmation. Production cannot publish placeholder founders/case studies/legal copy without owner acceptance.
- Draft content may appear only under authenticated authorized preview; no shared public preview links in v1. Preview uses noindex and private no-store response.
- Published slug changes create an explicit redirect record or require owner acknowledgement; simplest v1 locks published slug until unpublishing with clear warning. Do not leave broken links silently.
- Used packages/services/consultation types should unpublish rather than delete. Historical submissions retain snapshots. Referenced content deletion is restricted; explain dependencies.
- Media delete is blocked while referenced; after approved replacement remove unreferenced files via a controlled cleanup, not arbitrary paths from requests.
- Singleton settings/profile created idempotently. Seed initial services/packages/consultation types as drafts; no fabricated portfolio/testimonials/prices or default passwords.

## Appointment consistency
Requested times are preferences, not inventory reservations. Store requested local timezone plus UTC instants. Confirm requires assigned active owner/operations user, exact future UTC start/end and valid meeting URL if present. Use a DB transaction: lock assigned user row, then query overlapping confirmed appointments for that user where existing.start < new.end and existing.end > new.start. This serializes all confirmation/reschedule writes for the assignee. Adjacent slots are allowed. When changing assignee lock involved user rows in sorted ID order. Reject overlaps, cancelled/declined/completed invalid transitions and stale updates. Implement optimistic concurrency through updated_at/version for admin editing.

## Submission and email reliability
Generate per-form random submission UUID, bind it to session/form intent, verify server-side; unique submission_key handles same-request retries. Same UUID+payload returns same acknowledgement; different payload with used UUID yields a helpful conflict/new-form response. Never treat a cross-session UUID as authority to read another submission.

Persist submission and notification outbox rows in one transaction. Queue dispatcher after commit; scheduled dispatcher recovers unscheduled pending outbox records. Job checks delivery state, sends email and records result. Providers may accept email before a timeout: do not promise exactly-once delivery; use provider idempotency keys where supported and show uncertain failures honestly. No duplication of business records on email retry. Each confirmation/reschedule event has its own event_key; suppress stale reminder jobs by checking current schedule/event version.

## Storage/config
Public approved site media on public disk; disk/storage paths generated and isolated. No publicly served PHP, HTML, arbitrary SVG or scripts. Enquiry records private in DB. Future visitor attachments require private disk/authorized download and a separate review.

.env.example documents APP_URL, DB_*, MAIL_*, QUEUE_CONNECTION, SESSION_DRIVER, CACHE_STORE and optional trusted proxy settings. Notification recipients and secrets in environment/config, not editable public site_settings. Admin settings changes invalidate public content caches. Never cache private pages on public cache/CDN.

## Setup and operating docs
Create SETUP.md with actual Windows and Linux commands, PHP extensions, database creation/configuration, migrations, secure initial-owner creation, frontend build/dev, queue worker and scheduler. Do not use migrate:fresh against populated DB. Test DB must be distinct. Root public/ is web document root. Do not run artisan serve as production hosting. Exact server provisioning follows the chosen host later.
