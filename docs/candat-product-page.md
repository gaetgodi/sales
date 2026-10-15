# CANDAT product page (godindev.com/candat)

Status: live since 2026-10-15. Copy updated 2026-10-15 for the live recipe library (two tools available: calculator, recipe library). Operational record of what was built, where it lives, and how to update it.

## What it is

A product page for CANDAT under the Recipes section of Services, with a waitlist. CANDAT itself runs as a separate application at https://candat.godindev.com (not WordPress, not part of this repo). This page only presents it and collects interest.

## Where things live

| Item | Location |
| --- | --- |
| Product page | WordPress page ID 319, slug `candat`, https://godindev.com/candat/ (top-level page, default template) |
| Services entry point | Page ID 22 (`/services/`), Recipes section (`#recipes`): two paragraphs appended after the sandbox link, with a "Learn about CANDAT" link to `/candat/` (class `gdi-service-cta`) |
| Waitlist form | Fluent Forms form 5, "CANDAT Waitlist", embedded with `[fluentform id="5"]` in a Divi Code module |
| App links from the page | Calculator button links to `/go/candat-calculator/` (opens in a new tab), which the server redirects to https://candat.godindev.com/candatcalculator. See "App links and query strings" below |

The page is not in the header menu or footer links (deliberate: the footer links are covered by the hourly watchdog and the header stays at six items). Visitors reach it from Services.

## Page structure

Built as Divi 5 blocks, cloned from the About page (ID 24): section -> row -> column, containing:

1. H1 "CANDAT"
2. Intro text
3. Button "Open the calculator" (`gdi-service-cta`) and caption
4. Jump nav (`gdi-services-nav`, aria-label "CANDAT sections")
5. Five H2 sections with anchors: `what-it-is`, `available-now`, `whats-next`, `who-its-for`, `stay-in-the-loop`
6. Code module with the waitlist form
7. Closing line linking to `/contact/`

Notes: short Text modules render centered because the inherited column centers its children; this is the same behaviour as other pages. There is no SEO plugin, so the page has no custom meta description. Browser titles show the site name suffix (currently "Heart To Heart"), which is site-wide.

## Waitlist: form, feeds and tags

Form 5 fields: Name, Email, "I am a..." (University department, Researcher, Dietitian, Public health unit, Other; required), optional message, and a required, unchecked consent checkbox ("Yes, email me about CANDAT updates. I can unsubscribe at any time."). The stored entry is the consent record. Button: "Join the waitlist".

On submit:

- An email notification goes to sales@godindev.com, subject "New CANDAT waitlist signup".
- FluentCRM feed "Tag CANDAT waitlist" applies tags 13 and 14.
- FluentCRM feed "Tag by audience" applies one of tags 15-19 from the "I am a..." answer. (FluentCRM cannot combine fixed tags and routers in one feed, hence two feeds.)
- Both feeds: double opt-in on, force subscribe off, no list. No Fluent Boards card (a waitlist signup is not a sales lead).

Tags: 13 `interest:candat`, 14 `source:candat`, 15 `audience:university`, 16 `audience:researcher`, 17 `audience:dietitian`, 18 `audience:public-health`, 19 `audience:other`.

Contact form (form 3), feed "Tag by referring page": one router row added, referring page contains `/candat/` -> tag 14. The existing `interest:*` auto-reply funnels (4-9) are not triggered by the new tags.

## Email settings changed (global FluentCRM)

- From: Gaetan Godin <sales@godindev.com>; Reply-To: sales@godindev.com. This matches the single FluentSMTP/Brevo connection.
- Double opt-in email body customised: greeting, confirm button (spacer paragraph above it), "ignore if you didn't sign up" line, footer with business name and mailing address (`{{crm.business_name}}`, `{{crm.business_address}}`), contact address and an unsubscribe link (`##crm.unsubscribe_url##`). Subject remains the default. Stored as the `double_optin_settings` row in `fc_meta`.

## Verified on 2026-10-15

End-to-end test signup: confirmation email delivered to the inbox from the right sender with footer and unsubscribe link, confirm link worked, notification sent, contact tagged 13/14/16, consent recorded, no automations triggered, no board card. Test contact and entry were deleted afterward.

## Updating when a new CANDAT tool goes live

The page does not read from the app; the copy is manual.

1. Update the "Available now" and "What's next" Text modules on page 319, and the Services Recipes paragraph if it needs changing.
2. Edit Divi 5 content with `parse_blocks()` / `serialize_blocks()` and write with `$wpdb->update`. Do not use `wp post update` or `wp_update_post` on Divi pages (kses can mangle the blocks when run without a user).
3. Back up the old `post_content` first. Flush WP Super Cache (clear the cache directory or use the admin bar "Delete Cache"; `wp super-cache flush` is not registered here) and the object cache (`wp cache flush`).
4. Keep the claims policy below.

## App links and query strings

The CANDAT app rejects any request with a query string (nginx 400). Google's tag appends `?_gl=...` to links that leave godindev.com for another domain, so a direct link to the app broke for real visitors (found 2026-10-15, after launch; the first smoke test used a URL typed by hand and missed it).

Fix: the page links to a same-domain path, `/go/candat-calculator/`, and a rule at the top of the docroot `.htaccess` (outside this repo) redirects it with an empty query string:

    # BEGIN CANDAT redirect
    <IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^go/candat-calculator/?$ https://candat.godindev.com/candatcalculator? [R=302,L]
    </IfModule>
    # END CANDAT redirect

Rules for anyone editing this page:

- Never link straight to candat.godindev.com from a WordPress page. Add a `/go/...` redirect with the same trailing `?` and link to that.
- If the docroot `.htaccess` is ever replaced or regenerated, check that the CANDAT block is still there and still above the WordPress block.
- Test a new app link with `curl -sI "https://godindev.com/go/<name>/?_gl=test"`: expect 302 and a Location with no `?`. Check the target with a GET, not HEAD (the app returns 405 for HEAD).
- Preferred long-term fix: the app strips or ignores query strings on GET /candat and /candatcalculator. Once that ships, the redirect can stay as harmless.

## Claims policy for this page

- Describe only what is available now; mark everything else as planned or in development, with no dates.
- No accuracy figures, no pricing, no privacy or compliance claims.
- Recipes integration (connecting the Recipes site on godindev.com to CANDAT) is described as in development until it is live.
- The CANDAT app's own recipe library (Recipes, task 200) went live 2026-10-15. Visitors can view recipes; only the owner creates them. The page describes it as a recipe library and calls the other recipe tasks (management, nutrient analysis, cooking and yield factors, convert recipe to food) planned, matching the "Planned" badges in the app menu.

## Open items

- Site title ("Heart To Heart") appears in every browser tab and search result title.
- No meta description (no SEO plugin).
- Short Text modules center inside the column (site-wide behaviour); a per-module width of 100% would left-align them if wanted.
