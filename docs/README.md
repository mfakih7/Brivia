# BRIVIA — Website implementation specification

Version: 1.0 • Prepared: 2026-10-05 • Status: proposed baseline for owner review.

## Purpose
Build BRIVIA's public business website and custom content/operations administration. BRIVIA means Bridge + Via: the bridge from idea to product. Two senior developers, each with over ten years of experience in Lebanon and internationally, combine software delivery with project management experience.

The owner wants Laravel/PHP, dynamic packages/about/projects, no Filament or Livewire, premium design, strong security, and desktop/tablet/mobile quality. This bundle is an implementation contract, not an already-built application.

## Copy these files into the project
Copy this entire `docs/` directory into the existing Laravel project's `docs/`, replacing only the empty specification placeholders. Keep unrelated documentation. Assets live in `docs/assets/`. Put the supplied `CLAUDE.md` guidance into the project root by merging with existing instructions; never overwrite unrelated instructions.

## Read order and authority
1. Read this file.
2. Read 01 through 05 in full before planning a phase.
3. Inspect the logo and homepage mockup.
4. Read 06 for phase prompts; read PROGRESS for completed work.

Business behavior and security requirements override illustrative text or behavior in the image. The mockup governs visual direction. Explicit owner instructions override this proposed baseline. If documents conflict, report a concrete resolution for review rather than silently changing scope. Numbered reference documents are NOT phases. Do not implement each reference file independently.

## Files
| File | Responsibility |
| --- | --- |
| 01-business-and-pages.md | Business, routes, content, journeys and validation |
| 02-design-system.md | Brand tokens, page layouts, component and responsive rules |
| 03-architecture-and-database.md | Stack, code structure, schema and data integrity |
| 04-admin-and-workflows.md | Content CRUD, permissions and operational state changes |
| 05-security-and-quality.md | Controls, tests, performance and launch checks |
| 06-claude-prompts.md | Five implementation prompts with approval gates |
| DESIGN.md | Implemented design system (2026-10-09 refresh); supersedes 02 where they conflict |
| PROGRESS.md | Honest phase status, decisions, checks and handoff |
| assets/README.md | Asset roles and limitations |

## Baseline decisions
- Laravel 13, PHP 8.3, Composer 2, Blade, Tailwind CSS 4, Vite, minimal vanilla JavaScript, MySQL 8. Node version must satisfy the actual installed frontend packages. Confirm support on the target host.
- One Laravel application; server-rendered public pages and a custom Blade admin. No separate SPA/API application, Filament, Livewire, Nuxt or React.
- English only at launch; layouts should allow later translation. Arabic/RTL is future scope, not partially implemented.
- Appointment requests require manual confirmation. No live calendar integration or automatic slot booking.
- Package pricing supports fixed, starting-from and request-a-quote. Initial published package prices are not invented.
- No client accounts, checkout, payment gateway, invoices, chat, CRM integrations or client portal in version one.
- No public file attachments at launch. Only authorized staff upload public portfolio/content images. Enquiry documents can be added in a separately reviewed extension.
- Admin roles: owner, content_editor, operations_manager. Both founders can be owners if desired; bootstrap one account securely first.
- Notifications and reminders use queued email, with safe retry behavior.
- All content forms are structured; no arbitrary page builder or executable HTML.

## Owner inputs before production
Founder names, bios, roles, approved portraits; real contact email and notification recipients; WhatsApp/social URLs; approved case studies and publication permissions; final package deliverables/prices; appointment durations and availability preferences; hosting/domain; approved privacy and terms wording. Use clear admin-editable draft placeholders. Do not block local engineering for missing marketing content; do not publish invented content.

The homepage image includes fictional software examples marked Sample concept. They are not BRIVIA client work. Keep them labeled in development, omit from production portfolio unless the owner explicitly chooses to publish them as concepts.

## Five phases
1. Foundation, database and secure administration access.
2. Content administration and publishing.
3. Public website and responsive visual parity.
4. Enquiries, appointment requests, notifications and operations.
5. Integrated verification, fixes and deployment preparation.

After each phase, Claude reports changes, actual checks, manual review instructions and limitations; updates PROGRESS; asks for approval; and stops. Approval of one phase is not authorization to implement the next automatically. Never commit, push, deploy or contact real clients unless separately instructed.

## Definition of done
Public pages match the agreed direction at phone/tablet/desktop widths. Published database content drives the website. Admin authorization is enforced server-side. Forms persist correctly and show honest email/booking outcomes. Relevant security and workflow tests pass. Setup, operation, backups and deployment are documented. Missing credentials or content are reported as launch blockers, not concealed.

## Version verification
Laravel's official 13.x release notes specify PHP 8.3 minimum: https://laravel.com/docs/13.x/releases . Verify installed dependency constraints with Composer, and document exact installed versions. Never silently downgrade Laravel or upgrade PHP.
