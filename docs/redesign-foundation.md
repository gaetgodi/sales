# Redesign foundation (branch `redesign`, theme version 1.1.0)

Flat layout, one slim header, sitemap footer, four colour themes, and a mobile drawer on every page.
Everything below is in this repo; the Divi-database steps are listed at the end.

## Files

| File | Purpose |
|---|---|
| `00-tokens.css` | Primitives, the semantic colour layer (Brand = default, on `:root`), compact spacing tokens. Every pre-redesign `--gdi-*` name is kept as an alias: Divi's database settings reference some of them, so never remove or rename one. |
| `00-themes.css` | Overrides of the core tokens for `html[data-gdi-theme="parchment"]` and `"slate"`, plus the picker preview chips. |
| `01-components.css` | Trimmed: the wall, boxed wrapper, rounded region cards, gradients, old nav row, old drawer CSS and phone card hacks are gone (pointer comments mark where). Footer form, contact form, prose column and services nav remain, retokened. |
| `03-faq-testimonials.css` | Retokened; FAQ answers and author text now 16px; no shadows; visible focus. |
| `04-shell.css` | Flat page, interim preset flatten, header, logo, tagline, mobile drawer, cards, footer and sitemap, breadcrumbs, subnav, theme picker, skip link. |
| `05-a11y.css` | Global `:focus-visible`, 44px targets, reduced motion. Loaded last. |
| `includes/gdi-shell.php` | Head script, menu location, drawer markup, picker markup, skip link, four shortcodes. |
| `js/gdi-shell.js` | Drawer behaviour (focus trap, Escape, `aria-expanded`) and picker behaviour. |
| `functions.php` | Requires the shell, new enqueue order, old JS-built drawer removed, shortcode filter extended. The tagline rotation (`divi_sales_child_rotate_tagline`) is untouched. |
| `style.css` | Version 1.0.3 to 1.1.0 (the enqueue uses it for cache busting). |

Enqueue order: parent, fonts, tokens, themes, components, typography, faq-testimonials, shell, a11y, child `style.css`.
`images/texture-paper.png` is no longer referenced; the file is left in place.

## Tokens and themes

- Core tokens (themes override these): `--gdi-color-page, -surface, -surface-alt, -text, -text-secondary, -text-muted, -border, -control-border, -primary, -on-primary, -primary-text, -accent, -on-accent, -accent-light, -accent-text, -link, -link-hover, -focus, -header-bg, -header-text, -header-border, -logo-bg, -footer-bg, -footer-text, -footer-link, -footer-muted, -footer-focus, -success, -error, -overlay`, and `--gdi-shadow-color`.
- Legacy aliases (`--gdi-color-bg`, `-bg-alt`, `-page-bg`, `-boxed-bg`, `-content-bg`, `-content-frame`, `-card-border`, `-accent-hover`) follow the core tokens.
- Use `-primary` as a background (button text is `-on-primary`) and `-primary-text` for primary-coloured text, icons and borders on a surface (they differ in Slate). Use `-accent-text`, not `-accent`, when amber is text.
- `html[data-gdi-choice]` is the saved pick (`brand|parchment|slate|system`); `html[data-gdi-theme]` is the effective theme (`brand|parchment|slate`). `system` resolves to `slate` on a dark device, otherwise `brand`, in the head script, so the Slate values exist once.
- Spacing tokens (desktop / 980px / 767px): `--gdi-pad-card` 24/20/16, `--gdi-gap-grid` and `--gdi-gap-stack` 24/20/16, `--gdi-gap-section` 48/40/32, `--gdi-gutter` 24/20/16, `--gdi-header-h` 80/72/64, `--gdi-logo-h` 48/44/40, `--gdi-touch` 44, `--gdi-measure` 720, `--gdi-container` = `min(100% - 2 * gutter, 1200px)`. The old `--gdi-space-*` scale is unchanged.

## Theme picker

- Browser storage: `localStorage` key `gdi-theme`, values `brand | parchment | slate | system`. Anything else, missing or blocked storage means Brand.
- The inline script in `wp_head` (priority 1, before the stylesheets) sets the two attributes before first paint. It reads only the visitor's own storage, so cached page HTML is identical for everyone; no cookie, no server-side state. It is skipped in the Divi Visual Builder (`et_fb`).
- Placement: always in the drawer; `[gdi_theme_picker]` in a Code module places it in the footer or header; if no footer picker exists the script copies the drawer's into the footer. With JavaScript off the picker stays hidden and the site is Brand.

## Logo (hooks for the files you will supply)

- Height: `--gdi-logo-h` (48 / 44 / 40px). A trimmed logo drops in by replacing the image in the Divi header layout.
- Light logo for Slate: add a second Image module in the header layout with CSS class `gdi-logo-light` holding the light file, and give the normal logo module the class `gdi-logo-dark`. In Slate only the light one shows; in every other theme only the normal one shows.
- TEMPORARY (until a light logo exists): in Slate the existing logo sits on a light chip (`--gdi-color-logo-bg`, `#f4f6f6` in Slate, transparent elsewhere). The chip switches itself off when a `gdi-logo-light` module exists in the header. Remove this note and the token value once the light logo is live.

## Tagline

The tagline is no longer part of the header design. Put the Text module (CSS class `gdi-header-tagline`) in the Home hero. The rotation script in `functions.php` still finds it on any page. Any copy left in the header layout is hidden by CSS (`.et-l--header .gdi-header-tagline`) so it cannot affect header height.

## Shortcodes (place each in a Divi Code module; the existing render filter runs them)

- `[gdi_breadcrumbs]`: Home, page ancestors, current page. Empty on the front page.
- `[gdi_subnav]`: sibling pages of the current page (or its children on a parent page). Reuses `.gdi-services-nav`.
- `[gdi_footer_sitemap]`: columns from the WordPress menu location "Footer sitemap". Top-level items with children become columns; items without children collect under "Explore". With no menu assigned it shows the primary menu as one column.
- `[gdi_theme_picker]`: the appearance control.

## TEMPORARY interim CSS (remove when the presets are fixed in Divi)

`04-shell.css`, block "TEMPORARY interim flatten of the Divi presets", flattens row presets (`[class*="preset--module--divi-row--"]`) and section presets (`[class*="preset--module--divi-section--"]`, assumed by analogy). It also overrides per-row Builder padding. Fix the presets in Divi (Content Card padding to `var(--gdi-pad-card)`, radius to `var(--gdi-radius-md)`, no shadow) and delete the block.

## Still to do in Divi (staging, not in this repo)

1. One header layout for every page, including Home: logo left, one Menu module right (assign the same layout to the Home template).
2. Footer layout: place `[gdi_footer_sitemap]` (and optionally `[gdi_theme_picker]`), create the "Footer sitemap" menu, change the footer heading from H1 to a lower level.
3. Fix the Content Card and section presets (see above); check whether they store `4rem` literally or `var(--gdi-space-lg)`.
4. Set the Customizer body font size to 16px (the child theme also sets 16px on `body`, but Divi's inline CSS may win).
5. Build the Products hub page and menu (deferred).
6. Check Slate by eye for modules with hard-coded colours stored in the database.

## Checks recorded for this change

- Contrast: computed from the shipped CSS for Brand, Warm Parchment and Dark Slate (about 40 pairs each); all pass after the control-border fix. Slate logo chip: navy logo on `#f4f6f6` is 10.36:1.
- No colour literals remain outside `00-tokens.css` and `00-themes.css`.
- `php -l`: not run locally (no PHP installed); lint the changed PHP files under PHP 8.4 before activation.
