# S.T.R.In.G Website — stringlab.org

## Project Overview
Static HTML/CSS/JS website for S.T.R.In.G (Science, Technology, Research & Innovation Group) — an AI, automation and data engineering studio. No frameworks, no build step. Vanilla HTML5/CSS3/JS only.

## Server
Python HTTP server on port 5000 (`python3 -m http.server 5000`), configured as "Start application" workflow.

## Design System
- **Colors**: `--bg: #0B0B0C` · `--paper: #F2EEE6` · `--accent: #C6FF4A` · `--radius: 18px`
- **Fonts**: Newsreader (serif/display), Inter Tight (sans), JetBrains Mono (mono) — all from Google Fonts
- **CSS**: `assets/shared.css` (full design system, components, layout)
- **JS**: `assets/partials.js` (nav + footer injection, scroll behavior, intersection reveal)

## Architecture
- Nav and footer are injected via `assets/partials.js` into `<div id="nav-slot">` and `<div id="footer-slot">`
- Active nav route set via `data-route` attribute on `<body>` (e.g., `data-route="services"`)
- Every page links `assets/shared.css` and `assets/partials.js`
- SEO: every page has title, meta description, og tags, canonical link

## Page Inventory (33 pages + 2 utility files)

### Core pages (pre-existing)
- `index.html` — homepage
- `about.html` — about the studio
- `services.html` — services overview
- `solutions.html` — products overview
- `work.html` — case studies listing

### Service detail pages (5)
- `service-ai.html` — AI & Machine Learning
- `service-automation.html` — Workflow Automation
- `service-data.html` — Data & Analytics
- `service-product.html` — Web, App & Product Engineering
- `service-or.html` — Operations Research & Experience Design

### Solution/product pages (6)
- `solution-muneem.html` — Digital Muneem (accounting for Indian SMBs)
- `solution-edtech.html` — Learning Engine (adaptive EdTech LMS)
- `solution-remit.html` — Remit (blockchain cross-border remittance)
- `solution-forecast.html` — Forecast Kit (commodity/demand forecasting)
- `solution-stocksense.html` — Stock Sense (real-time inventory)
- `solution-docreader.html` — Automated Document Reader (OCR + LLM)

### Case study pages (6)
- `case-patanjali.html` — CPG · India · commodity forecasting
- `case-marex.html` — Finance · UK · blockchain remittance
- `case-retail.html` — Retail · India · inventory optimisation
- `case-statista.html` — Research · DE · conversational AI agent
- `case-biomass.html` — Energy · NL · OR supply chain optimisation
- `case-nirmala.html` — Interiors · India · AR room visualiser

### Company/About pages
- `team.html` — leadership team
- `careers.html` — job listings and hiring process
- `press.html` — media coverage and brand assets
- `process.html` — 5-phase engagement process + pricing models

### Functional pages
- `industries.html` — 7 vertical industries with use cases
- `contact.html` — contact form + office locations
- `book.html` — discovery call booking page
- `insights.html` — blog/articles listing + newsletter

### Legal & utility pages
- `faq.html` — FAQ with accordion (details/summary elements)
- `trust.html` — Trust Centre (security, compliance, DPA)
- `privacy.html` — Privacy Policy (GDPR + DPDP compliant)
- `terms.html` — Terms of Service
- `cookies.html` — Cookie Policy
- `404.html` — custom error page

### SEO utility files
- `sitemap.xml` — all 36 pages with priorities and changefreq
- `robots.txt` — allows all bots, points to sitemap

## Key Files
- `assets/shared.css` — full design system
- `assets/partials.js` — nav + footer injection, scroll behavior, reveal animations
- `assets/string-logo.png` — site logo
