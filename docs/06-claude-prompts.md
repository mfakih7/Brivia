# 06 — Five controlled implementation prompts

## How to use
Copy one prompt at a time into Claude from the Laravel project root. Keep the full docs directory available. After each phase review the implementation locally; explicitly approve or request corrections. Do not paste all prompts with an instruction to continue automatically.

Each phase is a milestone, not a single indivisible coding action. Claude may work through many files/tools within a phase. It must stop at the phase boundary. Reading 01–05 does not authorize all phases. If an earlier phase is incomplete, resolve it before dependent implementation.

## Shared rules applying to every prompt
Read README, all numbered references 01–05, PROGRESS, root CLAUDE instructions and assets before implementing. Follow owner overrides recorded in PROGRESS. Treat specs as a proposed baseline now authorized only to the phase scope requested. Resolve material conflicts with the owner. Keep code maintainable and avoid unnecessary abstractions/dependencies. Never use Filament/Livewire. Never invent case studies, prices, portraits, credentials or completed checks. Do not reset populated databases, overwrite unrelated changes, commit, push, deploy, email real clients or alter production without authorization. Use mail fakes/log transport in development until owner authorizes controlled delivery tests.

At the end of EVERY phase:
1. Run relevant checks; distinguish passed/failed/not run with reasons.
2. Update docs/PROGRESS.md, including versions, changed areas, decisions, tests, blockers and next phase.
3. Report what changed, concrete local review steps and limitations.
4. Ask “Do you approve Phase N so I can proceed to Phase N+1?” (Phase 5 asks for final review, not automatic deployment).
5. STOP. Silence or elapsed time is not approval. Do not start next phase.

---

## Prompt 1 — Foundation, schema and secure admin access

Implement **Phase 1 only** of BRIVIA following the shared rules and full specification.

Inspect the existing empty Laravel project and dependency versions first. Preserve existing work. Confirm Laravel13/PHP8.3. Configure Blade/Tailwind/Vite, central tokens and reusable public/admin shell without building full public pages.

Implement the database schema and integrity constraints from 03, migrations, models/relations/scopes, backed enums, factories and draft-only seed data. Create no fake completed projects or default passwords. Add privacy-safe audit primitives, queue/session/cache/outbox tables and protected public-media storage foundation. Database migrations require an explicitly configured local/test database; do not guess credentials or run destructive resets.

Implement admin login/logout, password reset, staff invitation foundations, secure first-owner bootstrap, active user checks, role policies, two-factor enrollment/challenge/recovery and recent-password confirmation. All admin data routes must block unauthenticated, inactive or pre-2FA users. Keep authentication UI custom Blade.

Add role-scoped dashboard skeleton and navigation, protected placeholders for future modules. Implement framework error pages. Add SETUP.md with actual local commands, database setup and account bootstrap.

Acceptance: Laravel boots; frontend production build and appropriate auth/authorization/schema tests pass; initial owner creation is secure; no public registration; final-owner safeguards; no sensitive values in logs/reports. Database relationships and public draft exclusion can be exercised through factories. Record any mail/config blockers honestly.

Stop for Phase 1 approval. Do not implement content CRUD or public marketing pages yet.

---

## Prompt 2 — Content administration and publication

Implement **Phase 2 only**, after confirmed Phase 1 approval. Follow shared rules and read existing implementation before changing it.

Build policy-protected custom admin CRUD/forms/lists for services, packages, categories/projects, team, company profile, homepage, FAQs, consultation types, public settings and legal pages as defined in 03/04. Include validation, list search/filter/pagination, structured repeatable fields, ordering, draft/published controls, authenticated preview and safe image uploads. Consultation type management is content CRUD; public appointment workflow remains Phase 4.

Implement reusable admin components and safe pricing/media/publication actions. Store project cover/gallery captions/alt/order, project origin and publication permission confirmation. Restrict referenced deletion, expose explicit unpublish, preserve historical record relationships. Implement owner staff invitation/role/deactivation UI on Phase1 primitives, final-owner checks and owner audit viewer.

Draft preview may use a minimal shared renderer now; Phase3 supplies final public styling. It must already be authorized and nonindexable. Implement cache invalidation foundations. Missing owner content remains editable draft placeholders; no fake public work.

Acceptance: authorized CRUD and publication tests, unauthorized role requests denied, price/media constraints, invalid URLs/XSS sanitization, referenced deletion, draft visibility and owner safety checks. Review resource forms on phone/desktop. Update operating instructions.

Stop for Phase 2 approval. Do not implement live enquiry/appointment workflows yet.

---

## Prompt 3 — Public website and responsive design

Implement **Phase 3 only**, after confirmed Phase 2 approval. Follow shared rules and inspect both reference images.

Build all public GET pages from 01 with design rules from 02: Home, Services/detail, Packages, Projects/detail, About, Consultation form presentation, Contact form presentation, Privacy, Terms and branded errors. Connect published database content and relationships; no duplicated hardcoded package/about/project content. Full form submission and notifications belong to Phase4; clearly mark disabled or pending interactions during this review rather than showing false successes.

Match navy/blue/cyan identity, header lockup, hero, card hierarchy, typography, section rhythm, footer and mobile layout. Use actual HTML text and components, not the mockup as a page image. Use licensed/approved assets or transparent local placeholders with a documented asset blocker. Keep concept project labels and prior founder experience labels accurate.

Implement service/package/project-to-contact preselection with validated published context; portfolio server-side filters/pagination; accessible mobile menu/FAQ; reusable field components; optimized images; SEO metadata/canonicals/OpenGraph/sitemap/robots excluding drafts/admin. Omit empty sections gracefully; no fabricated trust logos/testimonials.

Acceptance: public publication/404/filter/context tests; keyboard interaction review; screenshots at phone/tablet/desktop with honest reference comparison; no 320px overflow, image distortion, missing core actions or 200% zoom failures. Record asset/content differences and owner inputs still needed.

Stop for Phase 3 approval. Do not bypass security or invent backend form outcomes to make the mockup appear functional.

---

## Prompt 4 — Enquiries, consultation requests and operations

Implement **Phase 4 only**, after confirmed Phase 3 approval. Follow shared rules.

Connect contact and consultation forms end to end: backend validation, safe context resolution, privacy-version acceptance, rate limits, honeypot/timing, session-bound idempotency, transaction persistence and generic acknowledgement. No visitor attachments in version1. Preferred dates are requests, not reservations. Convert valid local IANA timezone input to UTC, reject DST-invalid/ambiguous times, enforce future/90day window.

Build permission-protected enquiry and appointment lists/details, assignees, notes/status history, explicit allowed transitions, confirmation/reschedule/decline/cancel/complete actions and actual dashboard counts. Confirm/reschedule must serialize overlap checks using MySQL row locking as specified. Use optimistic stale-edit protection.

Implement transactional notification outbox, queued acknowledgements/staff alerts/confirmation/reschedule/cancellation/decline, delivery state and recovery dispatcher. Add one eligible reminder ~24h before confirmed appointment; cancellation/rescheduling invalidates obsolete jobs. Display failed/uncertain mail outcomes honestly. Use fake/log mail locally; no unsolicited real emails during tests. Meeting URLs entered explicitly; no assumed Zoom/calendar integrations.

Acceptance: end-to-end submissions, validation/error preservation, duplicate submission, unauthorized operations, DST conversion, scheduling conflicts including a real MySQL concurrent test, stale writes, status transitions, email failures and recovery, stale reminder suppression. Demonstrate request→confirmation→reschedule→cancel with local test data. Update OPERATIONS.md.

Stop for Phase 4 approval. Do not deploy or contact actual prospects.

---

## Prompt 5 — Integrated quality, hardening and launch preparation

Implement **Phase 5 only**, after confirmed Phase 4 approval. Follow shared rules. Complete the existing scope; do not add speculative new features.

Review the integrated implementation against every acceptance requirement in 01–05. Fix concrete gaps in security, accessibility, performance, responsive design, content publication and business flows. Audit dependencies/configuration/storage/header policies. Run meaningful tests and production build; verify MySQL concurrency rather than claiming SQLite proves locking behavior. Use email fakes unless controlled delivery is explicitly authorized.

Capture required page/admin screenshots, review navigation/forms/loading/errors/empty states/zoom, and measure performance in a documented environment. Verify dynamic content updates, authorization boundaries, timezone/status transitions, queue/scheduler recovery and SEO privacy. Report any unrun check and reason.

Finalize SETUP.md, DEPLOYMENT.md, OPERATIONS.md and docs/PROGRESS.md. Document production environment variables without secrets; queue/scheduler/storage/backups/restore; non-destructive migrations, release rollback and limitations. Hosting-neutral instructions are acceptable if no provider selected, but cannot claim a tested host deployment.

Deliver final acceptance report with implemented scope, checks and results, screenshots, unresolved owner inputs, launch blockers and exact review commands. Do not classify missing production mail/domain/legal approval as passed.

STOP for final owner review. Do not commit, push, deploy, integrate paid services or send real client communication. Deployment requires a separate explicit instruction.
