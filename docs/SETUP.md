# Setup, Toast & launch checklist

## 1. Facts to confirm with Bigham's before launch

These came from the current site and either conflict or are missing. Fix them in wp-admin; schema, llms.txt and the footer update automatically.

- [ ] **Per-location phone numbers.** The current Locations page shows (877) 218-5965 / -5970 / -6058. Those look like SpotHopper call-tracking numbers that will stop working when SpotHopper is cancelled. Each location is seeded with the main line (806) 797-9931 for now. Use the number on each location's **Google Business Profile** so the name/address/phone details match everywhere.
- [ ] **Milwaukee Ave hours.** The homepage says the same hours as the other stores, but the Locations page says Sun–Thu 11am–9pm, Fri–Sat 11am–10pm, closed Monday. The site is seeded with the Locations-page version.
- [ ] **Drive-thru.** Which location(s) have one? Add "Drive-thru" to that location's Amenities.
- [ ] **Happy hour beer price.** The current menu heading says "$2.00 Beers" but lists $3.00. Seeded as $3.00.
- [ ] **Stuffed Baker Bar price per person** (not listed on the current site).
- [ ] **Map coordinates** for each location (right-click the pin in Google Maps → copy). These feed GeoCoordinates schema.
- [ ] **Google Business Profile URL** for each location (Location → "Google Business Profile / Maps URL") plus the brand-level one in Settings.
- [ ] **DoorDash store URLs**, if they're still active.
- [ ] Logo (SVG or transparent PNG), food photography and location exterior photos. Don't hotlink images from the SpotHopper CDN; it goes away when the account ends.

## 2. Toast POS connection

Toast offers read-only **Standard API access** to restaurant owners.

1. A Toast admin signs in to **Toast Web → Integrations → Toast API access → Manage credentials**. If that menu isn't there, ask Toast support to enable *Standard API access*.
2. Create one credential set covering all three locations, with read scopes for **orders, menus, config (dining options) and restaurants**.
3. Copy the **Client ID** and **Client secret** into *Bigham's → Settings → Toast POS*. Better still, put them in `wp-config.php` on SiteGround so they never sit in the database:
   ```php
   define( 'BH_TOAST_CLIENT_ID', '…' );
   define( 'BH_TOAST_CLIENT_SECRET', '…' );
   ```
4. Copy each location's **Restaurant GUID** (shown next to the location on the same Toast page) into its Location edit screen → *Toast Restaurant GUID*.
5. Go to Settings → **Test connection**. Each location should say OK.
6. Click **Backfill 90 days**. The dashboard fills in over the next half hour or so, then updates every hour.
7. Optional: tick *Check website menu prices against Toast daily*. Mismatches show on the dashboard. The website menu is never overwritten.

What's stored: daily totals, item quantities and sales, and orders per hour for each location. No customer names, cards or contact details.

> WP-Cron runs when someone visits the site. On SiteGround, set a real cron job (Site Tools → Devs → Cron Jobs) to run every 15 minutes:
> `cd /home/customer/www/bighams.yroc.host/public_html && wp cron event run --due-now >/dev/null 2>&1`
> Then add `define( 'DISABLE_WP_CRON', true );` to wp-config.php.

**Website menu = Toast menu.** With the credentials in, go to *Bigham's → Settings → Toast tools*. Click **Preview menu import** to see what will be added, repriced or removed, then **Import menu now**. After that the website shows exactly what Toast online ordering shows: the same sections, items, order and prices, with only online-visible items included. Photos, badges, homepage features and longer website descriptions are kept. Items not on Toast move to Draft and are never deleted. Set *Website menu source* to *Sync from Toast automatically every morning* to keep them in step without clicking. The menu is copied from the first location with a GUID unless another Restaurant GUID is set. Toast menus whose name contains "Catering", "Happy Hour" or "Special" feed those sections of the site.

**Toast ordering links** (pickup at each location) are already set. Catering uses Toast Catering & Events: `https://www.toasttab.com/catering/locations/8a09b080-…`. Toast gift card and loyalty links can go in Settings once you have them.

## 3. Catering leads

- The website quote form saves each inquiry under *Bigham's → Catering Leads* and emails the addresses in *Settings → Send new inquiry alerts to*.
- Update **Status** and **Quoted / booked value** on each lead. These feed the dashboard pipeline, booked revenue and close rate.
- To send leads to GoHighLevel, create an *Inbound Webhook* trigger in a GHL workflow and paste its URL into *Settings → CRM webhook URL*. Every inquiry is POSTed there as JSON.
- SiteGround's PHP mail can land in spam. Install an SMTP plugin (e.g. WP Mail SMTP with the client's Google Workspace or Microsoft 365) before launch.

## 4. AI search / SEO verification (after each deploy)

- [ ] `/llms.txt` and `/llms-full.txt` load and show the right hours and prices.
- [ ] Google **Rich Results Test** on Home, Menu, Catering and each Location. Expect Restaurant, Menu, FAQPage and BreadcrumbList with no errors.
- [ ] `/robots.txt`: staging should show `Disallow: /`. Production lists the AI crawlers and the sitemap.
- [ ] Every location page has a unique title and meta description that include its cross streets.
- [ ] *Bigham's → Dashboard → Search & AI readiness* is at 100%.

## 5. Go-live (bighamsbbq.com)

1. Point the domain at SiteGround, or clone staging to production with SiteGround's Staging tool.
2. Search-replace `bighams.yroc.host` → `bighamsbbq.com` (`wp search-replace`).
3. Confirm *Settings → Production domain* = `bighamsbbq.com`. The noindex comes off automatically.
4. Add **301 redirects** from the old SpotHopper URLs (e.g. with the Redirection plugin) so existing Google rankings carry over:

   | Old URL | New URL |
   |---|---|
   | `/lubbock-bigham-s-smokehouse-food-menu` | `/menu/` |
   | `/lubbock-bigham-s-smokehouse-catering` | `/catering/` |
   | `/lubbock-bigham-s-smokehouse-catering-information` | `/catering/` |
   | `/lubbock-bigham-s-smokehouse-locations` | `/locations/` |
   | `/lubbock-bigham-s-smokehouse-party` | `/parties/` |
   | `/lubbock-bigham-s-smokehouse-party-informations` | `/parties/` |
   | `/accessibility-page-01` | `/` (or an accessibility page) |

5. Submit `/wp-sitemap.xml` in Google Search Console and Bing Webmaster Tools. Bing feeds ChatGPT and Copilot search.
6. Update each Google Business Profile's website link to its location page (e.g. `/locations/82nd-street/`), not the homepage.
7. Add a production deploy job: copy the `deploy` job in `.github/workflows/deploy.yml`, give it a `production` environment with its own secrets, and trigger it on a `production` branch or on release tags.
