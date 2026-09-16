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

**Rule for future edits:** as with `et_footer_layout` (see `docs/footer-legal-links.md`), never
reconstruct post 23's whole `post_content` from an old copy — read current content, change only the
block you mean to change, write it back. A safe way to do this outside the Builder is
`parse_blocks()` + modify the one block's attrs + `serialize_blocks()`, which round-trips Divi's
JSON-escaped block format exactly (verified: re-serializing this page's untouched content reproduces
it byte-for-byte).
