# S.T.R.In.G Design System v1.0

## Brand idea

**Make complex operations legible.** S.T.R.In.G is a technology partner for AI, data, automation, product engineering and optimization. The brand should feel intelligent, candid and operationally grounded - more like an excellent technical briefing than a generic innovation campaign.

The visual contrast is deliberate: deep ink carries rigour; warm paper brings clarity; acid green signals the live insight, action or best path.

## Core palette

| Role | Hex / value | Use |
| --- | --- | --- |
| Ink canvas | `#0B0B0C` | Primary background, dark decks and image fields |
| Paper | `#F2EEE6` | Light sections, documents, contrast panels |
| Acid green | `#C6FF4A` | Active state, key KPI, route, CTA - use sparingly |
| Card | `#111113` | Elevated dark surface |
| Raised card | `#17171A` | Hover or secondary dark surface |
| Primary light text | `#F2EEE6` | Headings on ink |
| Secondary light text | `#C9C6BD` | Body text on ink |
| Muted | `#8A8A88` | Labels, metadata, secondary annotations |
| Ink-on-paper | `#0B0B0C` | Primary text or rules on light surfaces |

Never introduce a second saturated brand colour. Use red, amber and blue only when they carry product data meaning, and make their purpose explicit in a legend.

## Typography

| Role | Typeface | Treatment |
| --- | --- | --- |
| Display / editorial headline | Newsreader | 300-400 weight, tight tracking (-2% to -3%), large and calm |
| Body / UI | Inter Tight | 400-500 weight, direct, compact but never cramped |
| Label / data / metadata | JetBrains Mono | 10-12 pt / 11-12 px, uppercase, +12-14% tracking |

Fallbacks: Times New Roman for Newsreader, Arial for Inter Tight and Menlo for JetBrains Mono. Do not substitute a geometric sans for the display face: the serif is the key human counterweight to technical systems.

### Type hierarchy for print and slides

| Role | Slide size | Print / document size | Notes |
| --- | --- | --- | --- |
| Display headline | 52-72 pt | 36-56 pt | Max 2 lines |
| Slide title | 36-44 pt | 24-30 pt | Prefer a sentence, not Title Case fragments |
| Section label | 11-13 pt mono | 8-10 pt mono | Uppercase + tracking |
| Body | 18-22 pt | 10.5-12 pt | 1.35-1.55 line height |
| Caption / source | 10-12 pt mono | 7.5-9 pt mono | Bottom aligned where possible |

## Layout and rhythm

- Use a simple 12-column grid for 1440 px screens, 12 columns for 16:9 slides, and 6 columns for social tiles.
- Outer margin: 40 px desktop / 20 px mobile. Slides: 6-8% of width. Keep a generous empty edge.
- Base spacing unit: 8 px. Use 8, 16, 24, 40, 80 and 120; avoid arbitrary gaps.
- Large fields of quiet space are part of the design. Do not fill every available area with cards, texture or copy.
- Corners: 18 px for cards; fully rounded only for buttons, tags and compact status chips.
- Rules are 1 px and low contrast (`white 12%` on ink / `ink 12%` on paper).

## Reusable components

### Eyebrow label

Small uppercase mono text. Precede it with a 6 px acid-green dot when identifying a category or live state. Example: `● CASE STUDY / RETAIL`.

### Display headline

Newsreader, low weight, sentence case. Use an italic acid-green word only when it carries the focus. Example: `Turn operational noise into <em>signal.</em>`

### KPI

Large Newsreader number with small mono label beneath. Use green on just the unit, plus sign or one critical value - not every number.

### Card

Dark card on dark canvas with a low-contrast border, 18 px radius and 32 px inner padding. Inside: index/label, serif title, concise body and mono action. Avoid dense dashboard cards.

### CTA / pill

Acid-green filled pill, ink text, 14 px Inter Tight medium. Use direct verbs: `Start a conversation`, `See the work`, `Read case study`.

### Data visual

Lead with one conclusion. Use a warm-paper chart field or restrained dark field, direct labels and an acid-green highlight for the selected series/path. No 3D charts, gradients or dense legends.

## Accessibility and production rules

- Use paper text on ink and ink text on paper for all paragraph copy. Acid green is an accent, not a body-text colour.
- Do not communicate status by colour alone; add a label, icon, value or pattern.
- Minimum body size: 16 px web, 10.5 pt print, 18 pt slides.
- Preserve high-resolution source files; export SVG for logo/vector assets, PNG for UI screenshots requiring transparency, and WebP/JPEG for photography.
- Provide descriptive alt text for every web image. Name files by subject and format: `stringlab-automation-workflow-hero-16x9.webp`.
