# 04 — Custom admin and workflow specification

## Permissions
All admin routes require authenticated active staff and completed two-factor setup/challenge. No public registration. Policy checks on every write/read and file upload; hiding buttons is supplementary.

| Capability | Owner | Content editor | Operations manager |
| --- | --- | --- | --- |
| Dashboard | All relevant counts | Content summary only | Enquiry/appointment counts |
| Content CRUD, preview, publish | Yes | Yes | No |
| Site public settings/legal copy | Yes | Yes | No |
| Enquiry/appointment data and notes | Yes | No | Yes |
| Confirm/reschedule appointments | Yes | No | Yes |
| Invite/deactivate/change staff role | Yes | No | No |
| Audit log | Yes | No | No |
| Own password/2FA | Yes | Yes | Yes |
| Hard deletion of personal records | Yes, explicit action | No | No |

Preserve at least one active owner; prevent self-removal of final owner and mass assignment of roles. Deactivating staff revokes sessions and requires reassignment/clear handling of future appointments. Content editors cannot see client data via dashboards/search/exports.

## Navigation and screen patterns
Sidebar: Dashboard; Services; Packages; Projects; Team; About; Homepage; FAQs; Consultation Types; Enquiries; Appointments; Settings; Legal; Staff; Audit Log. Show only allowed modules. Header shows account, security setup and logout.

Lists have server-side search/filter/pagination (20/page), stable ordering, status chips, edit/preview actions and empty states. Search inputs bounded/debounced if enhanced; backend remains authoritative. Resource forms use field validation and preserve edits on errors. Require explicit confirmation for destructive actions. Reordering validates all submitted IDs and uses a transaction.

## Content modules
| Module | Form sections / required behaviors |
| --- | --- |
| Services | Title/slug/summary/body, deliverables, engagement steps, icon allowlist, order, SEO, publish controls |
| Packages | Title/slug/summary, editable feature/exclusion rows, pricing mode/amount/currency/label, services, featured/order, SEO, publish controls |
| Projects | Title/category/origin/client display, summary/contribution/problem/approach/solution/outcomes, technologies, services, cover/gallery ordering/captions/alt, permitted URL, permissions checkbox, featured/order, SEO |
| Team | Name/role/bio/experience/skills/portrait/social URLs/order, draft/published |
| About | Story, mission, intro and values rows |
| Homepage | Hero and CTA labels/allowed routes, headings, process rows and section visibility; no arbitrary HTML/JS |
| FAQs | Question/answer/placement/order/status |
| Consultation Types | Name/description/duration/pricing/order/status |
| Settings | Public contact/social details, budget/timeline choices, default SEO and timezone; no secrets |
| Legal | Privacy/terms structured text, version, publication with owner review note |

Drafts should remain stable while editing: publish state must not change by saving ordinary fields. Save Draft on a published item should either explicitly create an unpublished version (if revisions implemented) or warn and unpublish after confirmation; do not silently hide the live page. V1 can use Save Changes for published items, Save Draft for draft items, and separate Publish/Unpublish. Full versioned CMS revisions are out of scope. Preview reflects saved content only, labeled clearly.

## Enquiry operations
Status transitions: new → in_review/contacted/spam; in_review → contacted/qualified/closed/spam; contacted → qualified/closed/spam; qualified → closed; closed/spam → in_review by explicit reopen. Record actor/status change. Internal notes remain private. Detail shows contact, message, context snapshot, consent timestamp/version, assignee and status history. Never render visitor text as HTML.

Allow authorized staff to copy contact details or open mailto; do not auto-send a reply or proposal from the admin in v1. Intake acknowledgements and appointment transactional emails are the only automatic communications. Dashboard shows current counts and recent records scoped by permissions.

## Appointment operations
| Transition | Required input and effect |
| --- | --- |
| requested → confirmed | Assigned staff, exact start/end, timezone display, optional meeting URL; conflict check; client confirmation + event |
| requested → declined | Reason; client decline email; event |
| requested → cancelled | Reason; cancellation email; event |
| confirmed → confirmed (reschedule) | New exact start/end and optional assignee/link, reason; conflict check; reschedule email + event |
| confirmed → cancelled | Reason; cancellation email; invalidate reminders |
| confirmed → completed | Start time has passed; explicit staff action; no automatic outcome claims |

Completed/cancelled/declined records cannot return to confirmed in v1; create a new request if necessary. Internal notes do not trigger client email. Display visitor and staff timezone labels clearly. Default staff timezone Asia/Beirut; calculate DST through timezone database, never a fixed UTC+2/+3 offset.

Meeting links must be explicitly entered and validated, not invented. Confirmation email can say “Meeting details will follow” when none exists. No automatic Zoom/Meet account integration.

One reminder about 24h before confirmed start; if confirmed inside that window send confirmation only. Scheduler and jobs recheck active status/current version; no reminders after cancellation. Avoid retries resending a cancelled/rescheduled meeting's obsolete details. Delivery failure appears to staff without reverting valid business status.

## Staff access and security screens
- Initial owner through secure interactive artisan command; no hardcoded or committed password.
- Owner invites staff: email/role, single-use hashed token, expiry 48h; invitation contains setup link, no password. Owner-requested invitation is an explicit staff action.
- Acceptance: set strong password, verify invited identity, enroll two-factor before admin content access.
- Two-factor: enroll TOTP, confirm with current code, show recovery codes once, store encrypted; require recent password confirmation for disabling/regenerating. Last owner cannot lose access without recovery path.
- Password reset: short-lived broker token, generic responses, throttled; revoke relevant sessions after reset.
- Never display token/password/secret in admin audit logs or phase report.

## Audit and retention
Record publish/unpublish, price changes, project permission changes, staff roles/activation, enquiry status and appointment scheduling. Keep limited changed fields; redact secrets and personal message bodies. Owners can browse/filter audit entries, not edit them.

Proposed retention pending owner approval: enquiries 12 months after closure; appointments 12 months after completion/cancellation; audit metadata 12 months; failed/sent delivery metadata 90 days; encrypted backups 30 days. No scheduled hard deletion until the owner approves retention and it is documented in privacy text. Provide owner-only explicit delete of eligible closed records with confirmation and related data cleanup. Published media cleanup is separate from personal data deletion. Do not export client data in v1.

## Acceptance scenarios
1. Content editor edits/publishes package; new content/pricing appears publicly, cache invalidated.
2. Draft project is absent from public list/detail/sitemap; authorized preview works; anonymous preview fails.
3. Referenced service/package delete explains dependency and offers unpublish.
4. Operations manager can view/triage enquiry but cannot edit services or staff.
5. Concurrent confirmations for same staff overlapping times result in one accepted, one conflict; no duplicate bookings.
6. Cancellation suppresses pending reminder; notification failure is visible without duplicate appointment.
7. Last active owner cannot be deactivated/demoted.
8. Account deactivation blocks existing sessions on next protected request.
