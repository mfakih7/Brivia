# BRIVIA — Implemented design system (refresh 2026-10-09)

This document describes the design **as implemented**. It supersedes `02-design-system.md` and the homepage mockup wherever they conflict, following the owner's redesign instruction of 2026-10-09. Brand identity is unchanged: navy, blue, cyan and white, Manrope for display, Inter for body text, and the BRIVIA logo in the header.

Before and after screenshots are in `docs/screenshots/redesign/{before,after}` (desktop 1440, tablet 768 and phone 390).

## Principles

- **Typography-led.** Hierarchy comes from type scale, weight and whitespace, not decoration. The homepage has no hero illustration.
- **Restrained accents.** Cyan marks small details only: eyebrow rules, index numbers and the active navigation indicator. Blue is reserved for primary actions and links. Each view keeps one solid-blue primary action per area.
- **Hairlines over shadows.** Surfaces use 1px `line` borders (`#E6ECF3`); shadows appear only as a soft hover lift on interactive cards.
- **Generous rhythm.** Sections are 64 / 88 / 112 px tall (phone / tablet / desktop), and headings never sit directly on top of content.
- **A layout per content type** rather than identical card grids everywhere:

| Content | Layout |
| --- | --- |
| Services | Shared icon cards (`x-public.service-card`) |
| Packages | Pricing columns; a featured package is shown inverted (navy) |
| Projects | Media-first cards, with no box around the text |
| Process and values | Numbered timeline columns |
| Mission | Full-width navy statement band |

## Tokens (`resources/css/app.css` `@theme`)

| Token | Value | Use |
| --- | --- | --- |
| `navy-950` | `#061528` | Header, hero, page headers, footer, featured surfaces |
| `navy-900` | `#0B2442` | Closing CTA band |
| `primary` | `#006BFF` | Primary buttons (white text 4.6:1) and accents |
| `primary-strong` | `#0055CC` | Primary hover, links and index numbers on light backgrounds |
| `cyan` | `#00CBEA` | Small accents on dark backgrounds only, never body text on white |
| `canvas` | `#F5F8FC` | Alternating light sections |
| `line` | `#E6ECF3` | Hairline borders |
| `ink` / `muted` | `#14243B` / `#526277` | Text on light |
| `on-dark-muted` / `on-dark-subtle` | `#B9C6D8` / `#8FA1BA` | Text on navy |

## Type scale

| Class | Size (fluid) | Weight / tracking | Use |
| --- | --- | --- | --- |
| `.h-hero` | 42 → 80 px | Manrope 700, −0.035em, line-height 1.03 | Homepage headline only |
| `.h-page` | 36 → 56 px | 700, −0.03em | Page titles |
| `.h-section` | 28 → 40 px | 700, −0.025em | Section titles |
| `.h-card` | 19 px | 700, −0.01em | Card and panel titles |
| `.lead` | 17 → 20 px | Inter 400, line-height 1.6 | Introductions |
| `.eyebrow` | 12 px | 700, +0.16em, uppercase, with a 24 px rule | Section labels |
| `.index-num` | 13 px | 700, tabular numbers | 01, 02… indices |

Headings use `text-wrap: balance` and wrap long words (`overflow-wrap: anywhere`), so admin-entered titles never cause horizontal scrolling.

## Buttons

All buttons are pill-shaped (`border-radius: 999px`), set in Inter 600 with tight letter-spacing, have a visible 2 px focus outline offset by 3 px (cyan on dark backgrounds), and nudge their trailing arrow on hover.

| Class | Height | Use |
| --- | --- | --- |
| `.btn` | 44 px | Default, and the minimum touch target |
| `.btn-lg` | 52 px | Hero, CTA band and form submit |
| `.btn-sm` | 36 px | Desktop header CTA and compact admin actions (always beside larger hit areas) |

| Variant | Appearance | Use |
| --- | --- | --- |
| `.btn-primary` | Solid blue | The single main action in an area |
| `.btn-secondary` | Hairline outline; on dark backgrounds, a translucent white outline | Alternative actions |
| `.btn-quiet` | Translucent white fill with a hairline border | The header's "Book a consultation", which sits in balance with the navigation text instead of competing with it |
| `.btn-link` | Text link with an arrow | Inline "View case study", etc. |
| `.btn-danger` | Red text and border | Admin only |

## Navigation

- **Header:** sticky solid navy bar, 68 px (phone) / 76 px (desktop), with the logo lockup on the left.
- **Desktop:** links use 15 px medium text. The active link is white with a cyan bar along the header's bottom edge (more than colour alone) and `aria-current`. A hairline divider separates the links from the quiet CTA.
- **Mobile and tablet (below 1024 px):**
  - A 44 px circular toggle with the icon swapping between ☰ and ×.
  - `aria-expanded` and the label update, and Escape closes the menu and returns focus to the toggle.
  - The full-width panel lists large links (17 px, 56 px rows) separated by hairlines; the active item has a cyan dot.
  - Stacked primary and secondary actions follow the links.
  - Without JavaScript, the menu is always shown.

## Page patterns

- **Page header** (`x-public.page-header`): navy band with breadcrumb, eyebrow, title (7 columns) and intro (5 columns, bottom-aligned), plus an optional actions or meta slot. Use the `compact` variant for legal pages.
- **Section heading** (`x-public.section-heading`): eyebrow, title and optional intro, with an optional right-aligned action.
- **Home:**
  1. Typography hero: eyebrow, headline with the brand's cyan full stop, subtitle and two large CTAs, with the credibility items as a numbered list in the right columns. On phones these stack.
  2. Services as icon cards: 4 columns on desktop, 2 on tablet and phone.
  3. Two large project cards.
  4. Package columns.
  5. About statement split.
  6. Process timeline.
  7. Navy CTA band.
- **Services:** a 2-column grid of the same cards at every width, with deliverables shown from 768px. **Service detail:** narrative with a vertical numbered engagement timeline, a sticky deliverables panel with its CTA, and related packages and projects.
- **Packages:** pricing columns, three across, or a balanced 2×2 when there is an even number. The featured package is inverted to navy. "Not included" uses a native disclosure. A custom-scope band follows.
- **Projects:** horizontally scrollable pill filters with `aria-current`, a 1 / 2 / 3-column media grid, and pill previous/next pagination. **Case study:** the cover overlaps the navy header; numbered narrative sections sit beside a sticky project-facts panel; then the gallery and related work.
- **About:** story split, navy mission statement band, founder cards (initials until approved portraits exist), numbered values and process.
- **Contact and Consultation:** form in a bordered panel, split into labelled fieldset sections ("About you", "Your project", …), with 48 px inputs, a 4 px soft focus ring and inline errors plus a focused summary. A navy "What happens next" / "How booking works" panel sits alongside: before the form on desktop, after it on phones.
- **Legal:** compact header with a 720 px reading column and version metadata.
- **Errors (403 / 404 / 419 / 429 / 500 / 503):** navy page with the logo, eyebrow code, title, message and two actions. No stack traces.
- **Footer:** logo lockup and coverage text, Explore / Work with us / Get in touch columns (only filled contact details and social links are shown), legal links only when published.

## Cards and tiles (refinement 2026-10-09)

**Service card (`components/public/service-card`).** The homepage and Services page share one component:
- A 40–48 px icon tile with a blue-to-cyan tint and inset ring; the icon comes from the service's allowlisted `icon_key`.
- Title (Manrope 700; 15 px on narrow phones, 18 px from 640 px, 20 px from 768 px) with `hyphens: auto` so long words wrap without clipping.
- Summary in 14–15 px muted text.
- An always-visible **"Explore service →"** link. The arrow flows with the last word, so it never strands on its own line.
- The whole card is clickable through that single link, with a keyboard focus ring on the card (`has-[a:focus-visible]`). Hover only lifts the card; no action depends on hover.
- Padding is 14 px on phones, 20 px on small tablets and 28 px from 768 px.
- The `detailed` variant (Services page) adds a deliverables checklist from 768 px. On phones the summary is clamped to 5 lines; full detail lives on the service page.

**Contact tile (`components/public/contact-tile`).** Used for "Other ways to reach us" on the Contact page, in two columns at every width:
- Stacked layout: a 32 px icon tile, a 12 px uppercase label, then a 14–15 px value with hairline border and hover/focus states.
- Email and phone use `mailto:`/`tel:`; WhatsApp opens in a new tab with a screen-reader notice; consultation links to the booking page; location is static and spans both columns.
- Only options configured in Admin → Settings render.
- Values are escaped, then given line-break opportunities after `@` and `.`, with `overflow-wrap: anywhere` as a last resort, so long emails wrap cleanly.

**Icons.** A single in-house stroke set (`components/icon`): 24 px grid, 1.75 stroke, round caps and joins. Icons are always `aria-hidden`, and every icon-only control carries a text label. No emoji or mixed icon libraries.

**Sticky header.** The header takes up space in the page flow (so it never covers the first heading), and `html { scroll-padding-top: 5.5rem }` keeps in-page targets such as `#main` clear of it. Verified at 320, 375, 390, 768 and 1440 px.

Screenshots are in `docs/screenshots/refinement/`: Home, Services and Contact at 1440, 768, 390 and 320 px, plus close-ups of the phone service grid and contact tiles.

## Removed in this refresh

- The decorative bridge hero illustration (`components/public/hero-art`).
- The FAQ feature (public sections, admin module, data and seeds), per the owner's instruction.
- The old `page-band` component, replaced by `page-header`. The interim numbered `service-row` was itself replaced by the new shared `service-card` in the refinement.

## Accessibility and verification standards

- WCAG 2.1 AA contrast. Cyan is never used as text on white.
- Visible focus on every interactive element.
- Touch targets of at least 44 px.
- No horizontal overflow from 320 px, including at 200% zoom.
- Motion is limited to transitions of 160–180 ms and respects `prefers-reduced-motion`.
