# /work/ case studies — content lives in the DB, not this repo

All four case studies on `/work/` (Buck Lake Lodges, Stouffville Pickleball Players, Recipes,
Brain Injury Association of Ontario) are **content**, not theme code: each is a Divi Text module's
`innerContent` inside the **Work** page (post 23), edited directly in the Divi Builder. Nothing
under this theme renders their copy — the nav anchors in the page's Code module
(`#stouffville-pickleball-players` etc.) are the only case-study-related markup that lives here.

Each case study follows the same shape within its Text module:

```html
<h2 id="...">Client Name</h2>
<p><em>One-line description &mdash; livesite.example</em></p>
<p>[What was needed — one paragraph, no label]</p>
<p>What was built:</p>
<ul>...</ul>
<p>[What changed — one closing paragraph, no label]</p>
<p><a href="...">See it live at ... &rarr;</a></p>
<p><a href="...">Try the sandbox ... &rarr;</a></p>
```

Tone per `docs/content-briefs.md`: matter-of-fact, proof-focused, not a sales pitch.

## Maintenance log

- **2026-09-16** — Stouffville Pickleball Players' "What was built" and "What was changed" refreshed
  (via `parse_blocks()`/`serialize_blocks()`, editing only that one block's value) to reflect the
  platform's current feature set: ladder + Ace/Queen events now feed one Glicko-based Club Rating,
  a full live event runner for Ace/Queen of the Courts, a from-scratch reporting system replacing a
  third-party plugin, plus the pre-existing passkey auth, photo booth, and forensic data recovery
  items. The stale "member dashboard" and "SMS notifications" bullets (no longer accurate) were
  dropped. "What was needed" and both live/sandbox links were left untouched.
- **2026-09-24** — Brain Injury Association of Ontario published for all visitors (courtesy
  sign-off received). Until now it was admin-only via two `render_block` filters in
  `functions.php` (`divi_sales_child_hide_pending_case_studies()` on a
  `.gdi-case-study--pending` wrapper div, and `divi_sales_child_hide_pending_nav_link()` on
  `<!--GDI_PENDING_LINK_START/END-->` markers around its nav link), plus a dashed-amber
  "Draft — pending sign-off" treatment in `01-components.css`. All of it was removed: the filters
  and CSS from the theme, and the wrapper div, flag paragraph and markers from post 23's two
  blocks (same `parse_blocks()`/`serialize_blocks()` method as above). BIAO is now a plain Text
  module like the other three. The old unpublished draft `work-copy` (post 249) still has the
  old markup and would show the draft label if ever published; delete it or don't publish it.

**Rule for future edits:** as with `et_footer_layout` (see `docs/footer-legal-links.md`), never
reconstruct post 23's whole `post_content` from an old copy — read current content, change only the
block you mean to change, write it back. A safe way to do this outside the Builder is
`parse_blocks()` + modify the one block's attrs + `serialize_blocks()`, which round-trips Divi's
JSON-escaped block format exactly (verified: re-serializing this page's untouched content reproduces
it byte-for-byte).
