# Header layouts — two of them, both live in the DB

Like the footer (see `footer-legal-links.md`), the headers are Divi Theme Builder layouts stored as
WP database records, not files in this repo. Git cannot restore them; post revisions can.

| Post | Type | Used by | Nav menu |
|---|---|---|---|
| **12** | `et_header_layout` | Default Website Template (post 19) — every page except Home | **Yes** — Row → Column → `divi/menu` block, rendering WP menu "Main Menu" (term 4, location `primary-menu`) |
| **163** | `et_header_layout` | Homepage template (post 148, `use_on: homepage`) | **No — by design.** No revision of 163 has ever had a menu block (checked back to 2026-08-31). Don't "restore" one. |

The nav is rendered by a plain `<!-- wp:divi/menu {"builderVersion":"5.11.0"} /-->` with no
attributes; its styling comes from this repo (`.et_pb_row_1_tb_header`, `.et_pb_menu_0_tb_header`
in `01-components.css`) and the menu items come from the WP menu, not the layout. Both class names
depend on the menu staying the **second Row** of post 12 — if it is re-added in a different position,
the nav background/centering rules stop matching.

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
