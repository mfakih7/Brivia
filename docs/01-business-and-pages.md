# 01 — Business, pages and journeys

## Positioning
BRIVIA helps founders and businesses create websites and applications, improve existing products and make informed technical decisions. Offer a clear discovery-to-launch process with senior development and project management involvement. Do not imply company age from founder experience. Use “Two senior developers. Over 10 years of experience each.” rather than “BRIVIA has 20 years of experience.”

Primary audiences: founders needing an MVP, businesses needing a website/internal system, organizations modernizing existing software, and teams seeking technical or delivery advice.

Primary CTA: Discuss Your Project. Secondary CTA: Book a Consultation. Never promise guaranteed revenue, timelines or security.

## Service structure
| Service | Description | Example deliverables |
| --- | --- | --- |
| New Products | Plan and build a new digital product | Discovery, website/web application, integrations, agreed MVP, testing and launch |
| Redesign & Modernization | Improve an existing product | Assessment, UX redesign, code restructuring, performance work, migration plan |
| Technical Consulting | Help clients choose a technical direction | Architecture review, technology advice, code/project assessment, written recommendations |
| Delivery & Support | Coordinate delivery and sustain a product | Requirements, roadmap, coordination, QA, launch and agreed maintenance |

Mobile apps may be discussed in enquiries; publish a specific mobile development capability only when the owner confirms delivery capacity. Packages do not automatically include indefinite maintenance, source migration, app-store submission, hosting or paid third-party services.

## Route map
| GET route | Page | Data / relations |
| --- | --- | --- |
| / | Home | Published services, featured projects, packages, founders, homepage sections, FAQ |
| /services | Services overview | Published ordered services |
| /services/{slug} | Service detail | Service, published related projects and packages |
| /packages | Packages | Published ordered packages and FAQs |
| /projects | Portfolio | Published projects, category filter and pagination |
| /projects/{slug} | Case study | Published project, gallery, services and technologies |
| /about | About us | Singleton company story, published founders and values |
| /consultation | Appointment request | Published consultation types and visible timezone choices |
| /contact | Contact | Public contact settings, selected package/service/project context |
| /privacy | Privacy | Published approved legal text |
| /terms | Terms | Published approved legal text |

POST /contact submits an enquiry. POST /consultation submits an appointment request. Use named routes and POST-redirect-GET. Invalid/unpublished slugs return 404. Paginated project lists use server-side filters with allowlisted category values, 9 projects per page, stable newest-first ordering (published_at then id). Keep query strings on pagination.

Do not expose numeric private record IDs or submission details in success URLs. Redirect to the form page with a session flash and random public reference. No public lookup endpoint for enquiries or appointments.

## Page content requirements
### Home
Order: navigation; dark hero; credibility line; service cards; featured sample/real projects; package preview; about teaser; delivery process; FAQ; final CTA; footer.
Hero: “The bridge from idea to product.”
Subtitle: “We plan, build, and improve digital products with senior expertise.”
Buttons: Discuss Your Project → /contact; Explore Our Work → /projects.
Credibility line: Senior development • Product strategy • Project management.
Service title: A clear path to your next product.
Project title: Ideas brought to life.
Package title: Choose your starting point.
About title: Built by experience. Driven by your vision.
Final CTA: Ready to bring your idea to life?
Process: Discover → Define → Design & Build → Test & Launch → Support. Each step has a short editable explanation.
Do not add fabricated client logos, metrics, awards or testimonials. Omit empty sections gracefully.

### Services and detail
Intro with a plain explanation, four service cards, process and CTA. Detail has who it helps, agreed deliverables, how engagement works, related work and enquiry link carrying validated context. Text must distinguish examples from commitments.

### Packages
Show Business Website, MVP / Custom Application, Redesign & Modernization, Consulting Session as initial draft content. Cards: name, short description, price label/currency if configured, features, exclusions, CTA. Details can expand accessibly on the same page; no package detail route required. Include a note that custom scope is confirmed through a proposal. Maintain a selected package on contact form. Never interpret package selection as purchase.

### Projects and case study
List shows cover, title, category, summary and concept/experience label when applicable. Case study: project context, client display name only if authorized, problem, role/contribution, approach, solution, gallery, technologies, measured outcomes only when supported, external website link if approved, next-project/related-project links, CTA. An “earlier founder experience” label is required for work before BRIVIA. Respect client confidentiality.

### About
Company meaning/story, mission, founder cards, individual biographies/skills, management capability, values and delivery approach. No stock faces pretending to be founders; show initials placeholders locally until approved photos arrive.

### Contact
Page intro, contact options and form. Fields: name, email, optional phone/company, enquiry type (new_product, improve_existing, consulting, general), optional service/package/project context, optional budget range, optional timeline, message, privacy acknowledgement. Budget/timeline values are owner-configurable select choices, not promises. Enquiry message must not require confidential credentials.
Success copy: “Thanks—your enquiry has been received. Our team will review it and get back to you.” Do not say email sent unless delivery actually confirmed; persistence is sufficient to acknowledge receipt.

### Consultation
Intro explains manual confirmation. Fields: name, email, optional phone/company, consultation type, preferred local date/time, IANA timezone, optional alternate local date/time, project summary, privacy acknowledgement. Display selected duration. On submit: “Your appointment request has been received. Your preferred time is not reserved until BRIVIA confirms it.” No automatic meeting link or calendar invite.

### Legal
Store editable plain/structured legal content; escape or sanitize. Draft starter outlines may explain intended handling but must be explicitly owner-approved before public publication. Never claim regulatory compliance without review. No nonessential tracking until consent behavior and provider are agreed.

## Shared visitor form rules
| Input | Rule |
| --- | --- |
| Name | Required, trimmed, 2–120 characters |
| Email | Required, valid address, max 254 characters |
| Phone | Optional, max 40; accept international format without assuming Lebanon |
| Company | Optional, max 160 |
| Message/summary | Required, 20–5000 characters |
| Context IDs | Optional; resolve only published allowed records; never trust hidden fields |
| Privacy | Required acceptance; record policy version and accepted timestamp |
| Date/time | Valid local time in chosen IANA timezone; future, within next 90 days |
| Alternate time | Optional; same rules and different from primary |

Reject nonexistent or ambiguous DST local times with a helpful instruction to choose another time; do not silently shift. Store UTC plus request timezone. All errors appear inline and in an accessible summary; preserve safe inputs after validation. Disable repeated clicks while submitting but enforce idempotency server-side.

## Visitor journeys
1. New idea → service/package → contact preselection → saved enquiry → staff review → proposal conversation.
2. Existing product → modernization service → contact → assessment conversation; public form never requests production access.
3. Consultation → type and preferred time → requested status → staff confirms exact time → confirmation email.
4. Portfolio → case study → Discuss a Similar Project → contact with project context.
5. General question → contact → triaged enquiry.

No conversion requires a visitor account. Footer links include all key pages, privacy/terms and configured social/contact links. Empty contact/social settings must not render broken links.
