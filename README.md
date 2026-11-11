# Bigham's Smokehouse — WordPress build

Custom WordPress theme + companion plugin for **Bigham's Smokehouse** (Lubbock, TX · est. 1978 · 3 locations), replacing the current SpotHopper site at bighamsbbq.com.

- **Staging:** https://bighams.yroc.host (SiteGround). Indexing is blocked automatically on every domain except `bighamsbbq.com`.
- **Built by:** Yroc Consulting

```
wp-content/
├── themes/bighams/          # Presentation only (templates, CSS, JS)
└── plugins/bighams-core/    # Content types, schema/AI-search, Toast sync, dashboard, catering leads
.github/workflows/deploy.yml # Push to main → rsync to SiteGround over SSH
docs/                        # Setup + client handoff notes
```

Content and business logic live in the **plugin**, not the theme, so a future redesign never loses menu, location, lead or sales data.

## What's included

| Area | What it does |
|---|---|
| **Locations** | One edit screen per location: address, cross streets, hours table, Toast order link, Uber Eats/DoorDash, amenities, Toast GUID. Hours drive the "Open now" badge, footer, schema and llms.txt. |
| **Menu** | Menu items grouped into sections (Restaurant, Specials, Happy Hour, Catering à la carte). Multi-size pricing ("Single \| 2.49" per line). Seeded with the full current menu. |
| **Catering** | Catering packages (price/person, minimums, included items, add-ons), a quote form that saves **leads** with a pipeline status (New → Contacted → Quoted → Booked → Completed / Lost), email alerts, and an optional CRM webhook (GoHighLevel etc.). |
| **SEO + AI search** | Connected JSON-LD knowledge graph (Organization → 3 Restaurant entities with hours/geo/OrderAction, Menu → MenuItem → Offer, catering Service + OfferCatalog, FAQPage, BreadcrumbList), auto meta descriptions, Open Graph, `robots.txt` that welcomes GPTBot/ClaudeBot/PerplexityBot/Google-Extended, `/llms.txt` + `/llms-full.txt` generated from live data, per-page "Search & Social" box. Steps aside automatically if Yoast/Rank Math is installed. |
| **Toast POS** | Hourly sync of orders via the Toast API → daily sales, orders, avg ticket, online share, catering sales, top items, busiest hours per location. Read-only; no guest PII stored. Daily menu-price check flags website prices that disagree with Toast. |
| **Dashboard** | *Bigham's → Dashboard*: sales KPIs vs prior period, sales by location, daily trend, top sellers, channel mix, busiest-times heatmap, catering pipeline + upcoming events, website conversions (Order/Call/Directions clicks per location), and a Search & AI readiness checklist. |
| **Tracking** | Cookie-free first-party counters for Order Online, catering order, call, directions and delivery clicks (mirrored to GA4/GTM if present). |

## Deploy setup (one time)

1. Generate a deploy key **without a passphrase** on your machine:
   ```bash
   ssh-keygen -t ed25519 -C "github-deploy-bighams" -f bighams_deploy -N ""
   ```
2. SiteGround → Site Tools → **Devs → SSH Keys Manager → Import** → paste `bighams_deploy.pub`.
3. GitHub → this repo → **Settings → Secrets and variables → Actions** → add:

   | Secret | Value |
   |---|---|
   | `SG_SSH_HOST` | SSH hostname (SSH Keys Manager → Manage → SSH Credentials) |
   | `SG_SSH_USER` | SSH username, e.g. `u123-abc456def` |
   | `SG_SSH_PORT` | `18765` |
   | `SG_SSH_PRIVATE_KEY` | full contents of `bighams_deploy` (private key) |
   | `SG_REMOTE_PATH` | `/home/customer/www/bighams.yroc.host/public_html` |
   | `SG_SSH_KNOWN_HOSTS` | *(recommended)* output of `ssh-keyscan -p 18765 <host>` |

4. Push/merge to `main` (or Actions → *Deploy to SiteGround* → **Run workflow**). The job lints PHP, rsyncs **only** `themes/bighams` and `plugins/bighams-core`, activates the plugin, flushes rewrites and purges the SiteGround cache. Uploads, other plugins and `wp-config.php` are never touched.

## First run on staging

1. WordPress admin → Appearance → Themes → activate **Bigham's Smokehouse**.
2. The deploy activates **Bigham's Core**, which imports the locations, full menu, catering packages, FAQs, reviews and pages (Home, Menu, Catering, Private Parties, Our Story, FAQ, Contact, News), sets the static homepage and pretty permalinks.
3. Appearance → Customize → *Bigham's Homepage*: upload logo, hero photo/video, section photos.
4. Bigham's → Settings: confirm phones/emails, add Google Business Profile URL, catering alert emails, GA4 ID, Toast credentials.

See [`docs/SETUP.md`](docs/SETUP.md) for Toast, launch and verification checklists.

## Local development

Any local WordPress (LocalWP, wp-env, DevKinsta) works: symlink or copy the two folders into `wp-content/`. No build step — plain PHP, CSS and vanilla JS.
