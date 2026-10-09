# 02 — Visual and responsive specification

> **Superseded in part (2026-10-09):** the owner-requested redesign is documented in [DESIGN.md](DESIGN.md), which takes precedence wherever the two conflict (buttons, navigation, typography-led hero without illustration, page layouts, FAQ removal). This file is kept unchanged below as the original reference.

## References and scope
Use assets/brivia-logo-reference.png for brand identity and assets/brivia-homepage-mockup.png for desktop/mobile art direction. The image is a concept, not literal production content or an executable layout. Inner pages, tablet views and admin are specified below and must be built consistently. The bridge hero artwork is decorative; keep headings/buttons as real HTML.

Do not use the whole mockup as a background image. Do not reuse its fictional project screens as proof of client work. Do not stretch the square logo into a header. Do not assume generated text establishes final requirements.

## Brand tokens
| Token | Value | Use |
| --- | --- | --- |
| navy-950 | #061528 | Hero, footer, admin sidebar |
| navy-900 | #0B2442 | Secondary dark panels |
| primary | #006BFF | Key actions and focus accents |
| cyan | #00CBEA | Decorative highlights, small accents |
| canvas | #F5F8FC | Light section backgrounds |
| surface | #FFFFFF | Cards and forms |
| ink | #14243B | Main light-section text |
| muted | #526277 | Secondary light-section text |
| border | #DCE5EF | Dividers and outlines |
| success | #157347 | Success text with light green background |
| danger | #B42318 | Error text with pale red background |

Values are proposed tokens inspired by the reference, not measured original logo colors. Verify contrast for actual combinations; cyan is not a default text color on white. Use darker blue #0055CC when white text on primary fails required contrast at the actual size.

Self-host Manrope (headings, 500/600/700/800) and Inter (body, 400/500/600/700), retain license notices. Fallback: ui-sans-serif, system-ui, sans-serif. Exact logo font is unknown; preserve supplied lettering or an approved vector replacement, do not claim these are the logo's fonts.

## Scale and spacing
| Component | Desktop | Tablet | Phone |
| --- | --- | --- | --- |
| Hero H1 | 64px / 1.08 | 48px / 1.12 | 36px / 1.15 |
| Inner-page H1 | 48px / 1.15 | 40px / 1.15 | 32px / 1.2 |
| Section H2 | 36px / 1.2 | 32px / 1.2 | 28px / 1.25 |
| Card H3 | 20px / 1.3 | 20px / 1.3 | 18px / 1.35 |
| Body | 17px / 1.65 | 16px / 1.65 | 16px / 1.65 |
| Helper/meta | 14px / 1.5 | 14px / 1.5 | 14px / 1.5 |
| Section vertical padding | 80px | 56px | 40px |
| Outer gutter | 32px | 24px | 20px |

Use rem units and fluid clamp() where appropriate. Container max 1200px. Spacing scale 4, 8, 12, 16, 24, 32, 48, 64, 80px. Cards 16px radius, form controls/buttons 10px, small chips 6px. Standard card padding 24px (phone 20px), gaps 24px (phone 16px). Prefer borders and subtle shadows over heavy elevation. Button min height 48px. Focus outline 3px with offset; never remove outlines without replacement.

## Responsive breakpoints
Mobile <768px; tablet 768–1023px; desktop ≥1024px. Do not target only these three widths: verify intermediate widths and zoom. No page-level horizontal overflow at 320px. Service grid 1/2/4 columns; project grid 1/2/3 on listing, two featured cards on desktop homepage; packages 1/2/3 or 4 only if readable. Fourth package wraps intentionally. The mockup compresses cards for presentation; production must prioritize readable content.

## Shared components
- Header: compact BRIVIA lockup left; Home, Services, Projects, Packages, About Us, Contact; blue Book a Consultation CTA. Height ~80px desktop/68px phone. Dark on hero, light or dark consistent header on inner pages. Active route indicated by more than color alone.
- Mobile/tablet navigation: collapse when links no longer fit. Accessible toggle with aria-expanded; Escape closes; keyboard focus remains usable; overlay traps focus only if implemented as modal; restore focus on close. No hover-only links.
- Buttons: primary blue, secondary outlined; visible hover/focus, disabled and loading states; icons decorative. Explicit descriptive labels.
- Cards: restrained icons, readable title/description, clear link; avoid nested interactive elements.
- Forms: persistent labels, required indicators, helper text, inline errors and summary; never placeholders as labels. Loading state retains dimensions.
- Footer: BRIVIA lockup, tagline, navigation, privacy/terms, available social links and editable copyright name/year.
- FAQ: native details/summary or fully keyboard-accessible accordion.
- Motion: 150–220ms transitions, optional small entrance effects; respect prefers-reduced-motion; no autoplay video or parallax requirement.

## Homepage composition
1. Dark navy hero with left copy (~55%) and right bridge artwork (~45%). Fine cyan accents; ample negative space. The logo's folded B inspires the artwork, but the compact header lockup stays clean. Phone stacks copy, CTAs then artwork. Avoid text embedded in raster artwork.
2. Credibility text below CTAs; no fabricated company logos.
3. White services section, four equally aligned cards.
4. Light selected-project section, two large 16:10 image cards on desktop.
5. Package preview with clear feature lists, quote labels and CTAs. About teaser can sit beside cards on wide screens only if readable; stack on tablet/phone.
6. Delivery process and FAQ, added as real sections despite not being fully illustrated in compact mockup.
7. Dark final consultation strip and compact footer.

## Inner pages
| Page | Layout |
| --- | --- |
| Services | Compact navy title band, four detailed cards, process strip, consultation CTA |
| Service detail | Breadcrumb, title/intro, deliverable list, engagement steps, related project cards |
| Packages | Title/intro, comparison cards, inclusions/exclusions, FAQ, custom-scope CTA |
| Projects | Intro, filter chips/select, responsive image grid, accessible pagination |
| Case study | Breadcrumb, title/category, wide cover, readable narrative sections, gallery, technologies, CTA |
| About | Intro/story, two founder cards, values and process; portrait initials until approved assets |
| Contact | Intro plus two-column contact information/form, stacked on tablet/phone |
| Consultation | Intro, manual-confirmation note, form plus “What happens next” panel; stacked on small screens |
| Legal | Narrow 760px reading column, title, updated date, structured text |
| 404/500 | Brand-consistent helpful message, Home/Contact links; no stack traces |

Project gallery: optimized images, useful alt text, optional accessible dialog viewer; no swipe-only access. If viewer complexity delays quality, use linked full-size approved images with explicit labels instead.

## Admin appearance
Custom light dashboard, navy sidebar (~240px), compact top bar with account/security links. Cards show actual enquiry/appointment counts, not decorative charts. Resource lists: search, filters, status, ordering, pagination, actions. Forms use semantic sections, side publication panel on desktop, stacked phone. Use explicit Save Draft, Preview, Publish/Unpublish actions. Mobile sidebar collapses; tables may scroll inside a labeled region or switch to cards. Never hide essential actions only because the screen is narrow.

## Assets and performance
Original logo/reference PNG is not transparent. Create approved header lockups/mark-only/favicons without changing identity; if unavailable use a legible wordmark temporarily and report asset blocker. Do not auto-trace the reference into a purported exact vector. A separately licensed/approved hero image is needed; CSS geometric approximation is acceptable locally until supplied. Never show the desktop/mobile mockup itself as the live hero.

Use WebP/AVIF where supported, explicit dimensions, srcset/sizes. Eager-load only the main above-fold image; lazy-load below-fold gallery images. Target hero ≤300KB and cards ≤150KB after quality review; original downloads need not share these limits. No layout shift from missing image dimensions. Include empty states when content is not available.

## Visual acceptance
Capture Home at 390/768/1440px, all inner page types at phone/desktop, admin dashboard/resource edit at phone/desktop. Check 320px overflow and 200% zoom. Verify logo, palette, hierarchy, spacing, real text, keyboard focus and error states. Report visual differences from reference and their reason; never claim pixel parity based only on a successful build.
