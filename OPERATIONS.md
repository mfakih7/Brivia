# BRIVIA — Operations guide

This guide is for staff who run the website day to day. Setup is in [SETUP.md](SETUP.md) and hosting in [DEPLOYMENT.md](DEPLOYMENT.md).

## Roles

| Task | Owner | Content editor | Operations manager |
| --- | --- | --- | --- |
| Edit, preview, publish content; public settings; legal text | ✓ | ✓ | |
| Enquiries, appointment requests, notes, scheduling | ✓ | | ✓ |
| Invite and deactivate staff, change roles, view the audit log | ✓ | | |
| Approve placeholder founder and legal content; hard-delete closed personal records | ✓ | | |

Staff sign in with their email and password; two-factor authentication was removed at the owner's request on 2026-10-09. Sign-in is limited to 5 attempts per minute per email and IP. There must always be at least one active owner.

## Editing content

- **Drafts and publishing.** New items are always saved as **drafts**. Saving never changes whether an item is live. Use **Publish** and **Unpublish** in the side panel.
  - Publish checks required content first: summary and description, at least one deliverable or feature, a price amount for fixed or starting-from pricing, and for projects the permission confirmation, a cover image and alt text on every image.
  - Problems are listed in the side panel.
- **Preview** shows the saved version, including drafts. It requires sign-in, is never indexed, and has no shareable link.
- **Slugs (URLs)** are locked while an item is published, so links never break silently. Unpublish first if a URL must change.
- **Pricing.**
  - Packages stay in *Request a quote* mode until the owner approves a real price; quote mode never shows an amount.
  - Selecting a package on the website is never a purchase.
- **Deleting.** Items that are referenced cannot be deleted, and the message explains which references block it: services used by packages, projects or enquiries; packages or projects used by enquiries; consultation types with requests. **Unpublish** instead.
- **Ordering.** Change the numbers in the *Order* column and press **Save order**. Lower numbers appear first.
- **Long text** is plain text:
  - Separate paragraphs with a blank line.
  - Start a line with `## ` for a heading.
  - Start a line with `- ` for a bullet.
  - HTML is shown as text, never executed.
- **Concurrent edits.** If someone else saved an item after you opened it, your save is rejected. Reload, then reapply your changes.

### Images

- Accepted formats: JPEG, PNG or WebP, up to 5 MB and 6000 × 6000 px (25 megapixels).
- Minimum sizes: cover 1200 × 675, gallery 800 × 450, portrait 400 × 400.
- Each project can have up to 12 gallery images.
- Every upload is re-encoded to WebP at several widths, and metadata such as GPS is removed.
- Every image needs **alternative text** that describes it for people using screen readers.
- Upload only approved images:
  - Never use stock photos to represent founders. Initials are shown until an approved portrait exists.
  - Never use the design mockup's sample screens as client work.
- Replaced or removed images are deleted by the scheduler (`brivia:media-cleanup`, daily) once nothing references them.
- **Server requirement:** PHP `upload_max_filesize` must be at least 6M and `post_max_size` at least 8M. The development PHP currently has 2M.

### Projects and honesty rules

- **Origin:**
  - *BRIVIA project* for work done by BRIVIA.
  - *Earlier founder experience* for work done before BRIVIA. This is shown publicly.
  - *Sample concept* for fictional examples. This is shown publicly; in production only an owner can publish these.
- Name a client, link a live site or show screenshots only with permission. Tick the permission box to confirm. A published project cannot drop its permission without being unpublished first.
- Only list measured outcomes that can be supported with evidence.

### Placeholders and legal text

- Seeded founder cards and legal outlines are **placeholders**. In production they cannot be published until an **owner** presses *Mark as owner-approved*, after replacing or approving the text.
- Changing the text of a **published** legal page requires a **new version label**. Visitors' consent records keep the version they accepted.

## Staff access

- **Invite** from *Staff*. The email contains a single-use link valid for 48 hours. The invitee chooses their own name and password, then goes straight to the dashboard.
- **Deactivating** someone signs them out immediately. Reassign their open appointment requests first; the system blocks deactivation until you do.
- **Forgotten password:** use *Forgot your password?* on the sign-in page. The reset link is valid for 30 minutes and signs the person out everywhere else. Change your own password under *Security*.
- The **audit log** (owners only) records publishing, price changes, permission changes, staff changes and scheduling. It never stores passwords, tokens or message bodies.

## Enquiries (Admin → Enquiries)

Every contact-form submission is saved first. The visitor then gets an automatic acknowledgement email, and staff get an alert if `BRIVIA_STAFF_NOTIFICATION_EMAILS` is configured; the dashboard warns when it is not. Nothing else is sent automatically. **Reply from your own mailbox** using the email link or *Copy contact*.

- **Status flow:**
  - *New* → In review / Contacted / Spam
  - *In review* → Contacted / Qualified / Closed / Spam
  - *Contacted* → Qualified / Closed / Spam
  - *Qualified* → Closed
  - *Closed* or *Spam* → In review (explicit reopen)
  - Only valid next steps are offered, and every change is recorded in History and the audit log.
- **Assign** an enquiry to an active owner or operations manager.
- **Internal notes** are private and never emailed.
- **Context** shows the service, package or project titles the visitor chose, as they were at the time.
- **Consent** shows when the privacy notice was accepted and which version was accepted. While the privacy page is unpublished, this reads `draft:<version>`, which is honest.
- If two people edit the same record, the second save is rejected with "changed by someone else". Reload and retry.
- Visitors never send passwords or files. If one includes credentials in a message anyway, do not use them; ask them to change them.

## Consultation requests (Admin → Appointments)

Preferred times are **requests, not reservations**. Each record shows times in the visitor's timezone and in staff time (`BRIVIA_STAFF_TIMEZONE`, default Asia/Beirut). Conversions use the timezone database, so daylight saving is handled automatically.

| Action | When | What happens |
| --- | --- | --- |
| **Confirm** | Requested | Choose the staff member, exact date, start time, duration and the timezone you are entering times in (default staff time). Optionally paste a real meeting link; none is generated. The system rejects the change if the person already has an overlapping confirmed appointment (back-to-back is fine). The client gets a confirmation email ("Meeting details will follow" if there is no link), and one reminder is scheduled 24 h before. No reminder is scheduled if the start is less than 24 h away. |
| **Reschedule** | Confirmed | Same form plus a **reason, which is sent to the client**. Overlaps are checked again. The client gets a reschedule email, the old reminder is cancelled and a new one is scheduled. |
| **Decline** | Requested | A reason is required and is emailed to the client. |
| **Cancel** | Requested or confirmed | A reason is required and is emailed to the client. Pending reminders are stopped. |
| **Mark completed** | Confirmed, after the start time | Recorded only; nothing is emailed and no outcome is claimed. |

Completed, cancelled and declined appointments cannot be confirmed again. Ask the client to send a new request.

## Email deliveries (Admin → Email deliveries)

All automatic emails go through an outbox. Each one is saved in the same database transaction as the enquiry or appointment, then sent by the queue worker.

| Status | Meaning |
| --- | --- |
| Pending | Waiting to be sent, or scheduled (reminders). |
| Sent | The mail provider **accepted** the message. This does not guarantee it was delivered or read. |
| Failed | 3 attempts failed. The business record is unaffected. Use **Retry**, or contact the person directly. |
| Suppressed | No longer relevant, for example a reminder for a rescheduled or cancelled appointment. This prevents outdated details being sent. |

Error text never includes email addresses or secrets. Providers can occasionally accept a message and still report an error, so a retry may rarely produce a duplicate email; business records are never duplicated.

**The queue worker and scheduler must be running** (see DEPLOYMENT.md). The scheduler runs `brivia:notifications:dispatch` every minute. It sends due reminders and recovers any pending email whose job was lost.

## Deleting personal records

Only owners can permanently delete, and only **closed or spam** enquiries and **completed, cancelled or declined** appointments. Type the reference to confirm. Notes, history and delivery records are deleted with the record, and the audit log keeps only "deleted" with the record number. No scheduled automatic deletion exists until the owner approves a retention policy. The proposed policy, pending approval:

| Data | Proposed retention |
| --- | --- |
| Closed enquiries | 12 months |
| Completed or cancelled appointments | 12 months |
| Audit metadata | 12 months |
| Delivery metadata | 90 days |

There is no data export in v1.

## MySQL concurrency test

The scheduling lock is verified against a real MySQL/MariaDB server, using a **separate** test database whose name must end in `_test`:

```sh
mysql -u root -e "CREATE DATABASE IF NOT EXISTS brivia_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# PowerShell: $env:BRIVIA_TEST_MYSQL_DATABASE="brivia_test"; C:\php83\php.exe artisan test --filter=MySqlConcurrencyTest
BRIVIA_TEST_MYSQL_DATABASE=brivia_test php artisan test --filter=MySqlConcurrencyTest
```

Optional variables: `BRIVIA_TEST_MYSQL_HOST`, `_PORT`, `_USERNAME` and `_PASSWORD` (defaults `127.0.0.1`, `3306`, `root`, empty). The test only runs additive migrations on the test database and deletes the rows it creates.

## Backups, restore and maintenance ownership

| Task | Frequency | Owner (to be named) | Reference |
| --- | --- | --- | --- |
| Database + image backup (encrypted) | Daily (proposed) | Hosting/ops owner | DEPLOYMENT.md §5 |
| Restore drill on a separate database | Before launch, then quarterly | Hosting/ops owner | DEPLOYMENT.md §5 |
| Check Admin → Email deliveries for failures | Daily | Operations manager | above |
| Review staff accounts and audit log | Monthly | Owner | Admin → Staff / Audit Log |
| `composer audit`, `npm audit`, apply patch updates | Monthly and on advisories | Developer | DEPLOYMENT.md §8 |
| Media cleanup (automatic, daily) | Scheduler | — | `brivia:media-cleanup` |

## Local demo content

Development machines may contain demo content (see SETUP.md). The seeder and its cleanup refuse to run outside `local`/`testing`. Remove it before entering real content: `C:\php83\php.exe artisan brivia:demo-content:remove`.
