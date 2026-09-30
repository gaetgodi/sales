# Header layouts — two of them, both live in the DB

Like the footer (see `footer-legal-links.md`), the headers are Divi Theme Builder layouts stored as
WP database records, not files in this repo. Git cannot restore them; post revisions can.

| Post | Type | Used by | Nav menu |
|---|---|---|---|
| **12** | `et_header_layout` | Default Website Template (post 19) — every page except Home | **Yes** — Row → Column → `divi/menu` block, rendering WP menu "Main Menu" (term 4, location `primary-menu`) |
| **163** | `et_header_layout` | Homepage template (post 148, `use_on: homepage`) | **No — by design.** No revision of 163 has ever had a menu block (checked back to 2026-08-31). Don't "restore" one. |

The nav is rendered by a plain `<!-- wp:divi/menu {"builderVersion":"5.11.0"} /-->` with no
attributes; its styling comes from this repo and the menu items (and their order) come from the WP
menu, not the layout.

### Selectors: structural, not positional (since 2026-09-30)

The nav, footer heading/copyright rules in `01-components.css` and the mobile drawer script in
`functions.php` target structure:

| What | Selector |
|---|---|
| Nav row (teal bar, padding) | `.et-l--header .et_pb_row:has(.et_pb_menu)` |
| Menu (transparent bg, desktop fit-content centering) | `.et-l--header .et_pb_menu` |
| Drawer source links (`functions.php`) | `.et-l--header .et_pb_menu .et_pb_menu__menu > nav > ul > li` |
| Footer heading | `.et-l--footer .et_pb_heading .et_pb_heading_container h1` |
| Footer copyright | `.et-l--footer .et_pb_text` (+ `.et_pb_text_inner`) |

They used to target Divi's positional order classes (`.et_pb_row_1_tb_header`,
`.et_pb_menu_0_tb_header`, `.et_pb_heading_0_tb_footer`, `.et_pb_text_0_tb_footer`). Two problems
with those:

1. **They don't exist in the Visual Builder.** The canvas gives the same modules ID-based classes
   (`.et_pb_menu_6365e3b6-…`), so the nav showed as a white, full-width bar with no teal row while
   editing — even though the live site was fine.
2. **They shift with position.** Adding a Row above the nav made it `row_2`; adding another Menu
   module above made it `menu_1` — silently dropping the styling, and (for the drawer) the whole
   phone/tablet menu.

The structural selectors assume the header has **exactly one Menu module** and the footer **one
Heading and one Text module** (true as of 2026-09-30). If a second one is ever added, revisit them.

**Mobile drawer = the high-stakes one.** Below 980px Divi's own hamburger is always hidden
(`01-components.css`, "retired" rule), so if the drawer script finds no menu links it builds nothing
and phone/tablet visitors have no navigation at all. After any header layout change, open the site at
phone width and tap the ☰ button.

(Unrelated to position, one deliberate behavior note: the old footer copyright rule also declared
`margin: auto` / `padding: var(--gdi-space-md)` with `!important`, but Divi's own
`.et_pb_text_0_tb_footer{…!important}` tied it and loaded later, so they never applied. They were
dropped in the switch so the higher-specificity selector wouldn't suddenly change the live layout.)

## Incident 2026-09-30

| When | Revision | What |
|---|---|---|
| 15:57 | 276 | last revision with the menu before the incident |
| 18:11 | 283 | Builder save removed the whole menu Row (Row + Column + Menu block, nothing else) |
| 18:12 / 18:54 | 284 / 295 | intentional Section edits while the menu was gone: `justifyContent` center → start, `modulePreset: default` removed |
| 19:11 | 298 | menu Row re-added in the Builder — byte-identical to revision 276's, in the same position |

Net diff 276 → current is only the two Section edits, which were kept. The WP menu itself (6 items)
was never affected.

## How to check

```bash
wp post get 12 --field=post_content | grep -c 'wp:divi/menu'     # expect 1
wp menu item list main-menu --fields=title                        # expect the 6 items
```

## How to restore (if it happens again)

Same rule as the footer: never roll the whole layout back to an old revision — later intentional
edits would be lost. Find the newest revision with the menu, diff it against current content (split
on `<!-- wp:` to make it readable), and re-insert only the missing Row block verbatim at its original
position (after the first Row's closing `<!-- /wp:divi/row -->`, inside the Section).

```bash
wp post list --post_type=revision --post_parent=12 --fields=ID,post_modified,post_author
```
