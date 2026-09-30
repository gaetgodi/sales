# Footer links (FAQ, Testimonials, Terms, Privacy) — fragile, lives in the DB

**Status: present and rendering. Restored 2026-09-30 after a Divi Builder save dropped all four.**
**Watched hourly by `/root/bin/check-godindev-footer-links.sh` (emails on loss; see below).**

> **Before saving the footer in the Divi Builder:** reload the Builder tab first if it has been open
> for a while, or if anything changed post 13 since you opened it (wp-cli, another tab, a
> restore). A Builder save writes back the *whole* layout as the Builder holds it, so a stale tab
> silently reverts every change made since it loaded. **After saving:** run the check below.

## Where they actually live

The footer links are **content, not theme code**. They sit inside the `innerContent` of the
copyright **Text module** in the footer Theme Builder layout (`et_footer_layout`, **post 13**) — a
WP database record, not a file in this repo.

They were deliberately merged into the copyright module's own text (commit `a6dc53c`) rather than
kept in their own row/column, so the rendered line is one paragraph:

```
© Godin London Incorporated 2026 | All rights reserved · FAQ · Testimonials · Terms of Service · Privacy Policy
```

The markup appended after the dynamic-content year variable is:

```html
<span class="gdi-footer-links">&middot; <a class="gdi-footer-link" href="/faq/">FAQ</a> &middot; <a class="gdi-footer-link" href="/testimonials/">Testimonials</a> &middot; <a class="gdi-footer-link" href="/terms-of-service/">Terms of Service</a> &middot; <a class="gdi-footer-link" href="/privacy-policy/">Privacy Policy</a></span>
```

Terms/Privacy were added first; FAQ/Testimonials were prepended on 2026-09-20 (revision 254,
alongside commit `b72154a`).

Only the styling (`.gdi-footer-links` / `.gdi-footer-link` / `.gdi-footer-links-sep`) is in this
repo, in `01-components.css` (commit `509f198`). That CSS has never been the problem.

## Incident history

Nothing in the front end errors out when the links are lost. They just quietly stop existing, and
because the CSS is still present and correct it reads like a display bug rather than a content loss.
Revision history, not commit dates, shows when the content regressed.

| When | Revision | Cause | Lost | Fix |
|---|---|---|---|---|
| 2026-09-16 21:02 | 217 | wp-cli rewrite of the whole layout (Fluent Forms embed swap) built from a pre-`a6dc53c` copy | Terms, Privacy | 219, commit `e241006` |
| 2026-09-30 12:33 | 267 | Divi Builder save (admin user); same save also made two intended column-layout changes | all four | 268 (surgical insert of the span from 254) |

The 2026-09-30 save kept its intended layout edits (row-1 column `justifyContent: center`, copyright
column `display: flex; alignItems: center`) while the copyright Text block came back without the
span. Whether the Builder tab was stale (loaded before revision 254) or the Text module's editor
stripped the span is not provable from the DB. Either way, the reload-before and check-after habit
above catches it.

## How to check

Quick check (should print `OK: All four footer links present in post 13.`):

```bash
/root/bin/check-godindev-footer-links.sh --status
```

Or by hand (should print `1`):

```bash
wp post get 13 --field=post_content | grep -c gdi-footer-links
```

## Watchdog (root cron)

`17 * * * * /root/bin/check-godindev-footer-links.sh` — read-only. It checks that all four hrefs are
in post 13 and emails gaetgodi@gmail.com **once** when the state changes: when links go missing, when
the check itself errors (e.g. wp-cli failing under cron's minimal PATH), and when they are restored.
The last state is kept in `/var/tmp/godindev-footer-links.state`. The script is not in this repo (it
lives with the other root admin scripts in `/root/bin`).

## How to restore

Find the newest revision that still has the links, and lift the span verbatim rather than retyping
the escaped markup (Divi stores it JSON-escaped, e.g. `\u003cspan class=\u0022gdi-footer-links\u0022\u003e`,
lowercase hex):

```bash
wp post list --post_type=revision --post_parent=13 --fields=ID,post_modified,post_author
wp post get <REV_ID> --field=post_content | grep -o '<!-- wp:divi/text {.*} /-->' | tail -1
```

Diff the candidate revision against live content first (splitting on `<!-- wp:` makes it readable).
The goal is a **pure insertion** of the links span, so newer intended changes are not reverted along
with it. Both times so far, the latest save carried legitimate edits a full revert would have undone.

The 2026-09-30 restore did this in PHP: it took the text between the year variable's closing
`Y\u0022}}})$` and the next `"}}}` in revision 254, then inserted it at the same anchor in the
current content. The anchor must be unique, and the length must grow by exactly the span's length.
Then `wp_update_post(['ID' => 13, 'post_content' => wp_slash($new)])` (creates a revision), clear
`wp-content/et-cache/*` and run `wp cache flush`.

Gotcha: when writing such a script from a tool or heredoc, keep literal `\uXXXX` sequences out of the
source. Build the backslash with `chr(92)` instead, because some editors and tool layers decode the
escapes and the anchor then silently fails to match.

**Rule for future edits to post 13:** read the current `post_content`, modify the one block you mean
to change, write it back. Never reconstruct the whole layout from an older copy — and in the
Builder, never save from a tab that predates the latest change.
