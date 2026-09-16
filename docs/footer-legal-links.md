# Footer Terms/Privacy links — fragile, lives in the DB

**Status: present and rendering. Restored 2026-09-16 after a direct-DB rewrite dropped them.**

## Where they actually live

The Terms of Service / Privacy Policy links are **content, not theme code**. They sit inside the
`innerContent` of the copyright **Text module** in the footer Theme Builder layout
(`et_footer_layout`, **post 13**) — a WP database record, not a file in this repo.

They were deliberately merged into the copyright module's own text (commit `a6dc53c`) rather than
kept in their own row/column, so the rendered line is one paragraph:

```
© Godin London Incorporated 2026 | All rights reserved · Terms of Service · Privacy Policy
```

The markup appended after the dynamic-content year variable is:

```html
<span class="gdi-footer-links">&middot; <a class="gdi-footer-link" href="/terms-of-service/">Terms of Service</a> &middot; <a class="gdi-footer-link" href="/privacy-policy/">Privacy Policy</a></span>
```

Only the styling (`.gdi-footer-links` / `.gdi-footer-link` / `.gdi-footer-links-sep`) is in this
repo, in `01-components.css` (commit `509f198`). That CSS has never been the problem.

## The failure mode to watch for

**Any wholesale rewrite of post 13's `post_content` via wp-cli/SQL will silently delete these links**
unless the new content is built from the *current* stored content.

That is exactly what happened on 2026-09-16 21:02 (revision 217): the footer contact form was
swapped to the Fluent Forms embed (`[fluentform id="4"]`) by writing a fresh block payload for the
whole layout. The payload was assembled from a pre-`a6dc53c` copy of the footer, so the copyright
module came back without its links suffix. Note the trap: the *code* side of that work was committed
on 2026-09-03 (`4379cfa`), but the *DB* side landed two weeks later — so the commit dates do not
point at when the content regressed. Revision history does.

Nothing in the front end errors out when this happens. The links just quietly stop existing, and
because the CSS is still present and correct it reads like a display bug rather than a content loss.

## How to check, and how to restore

Check (should print `1`):

```bash
wp post get 13 --field=post_content | grep -c gdi-footer-links
```

Restore: find the newest revision that still has them, and lift that Text block verbatim rather than
retyping the escaped markup (Divi stores it JSON-escaped as `<span ...`, lowercase hex):

```bash
wp post list --post_type=revision --post_parent=13 --fields=ID,post_modified
wp post get <REV_ID> --field=post_content | grep -o '<!-- wp:divi/text {.*} /-->' | tail -1
```

Diff the candidate revision against live content first — the goal is a **pure insertion** of the
links span, so that unrelated newer changes (e.g. the Fluent Forms embed) are not reverted along
with it.

**Rule for future edits to post 13:** read the current `post_content`, modify the one block you mean
to change, write it back. Never reconstruct the whole layout from an older copy.
