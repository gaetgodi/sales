# godindev.com — Design System Reference (v2)

**Purpose:** keep this open beside you while working in Divi. Copy-paste values directly into module settings.

**Status:** all decisions below are final for godindev.com. Brand colors match godin.com exactly; typography, neutrals, and scale were decided deliberately for godindev.com (not inherited from godin.com's original, less-considered setup) and are candidates to port back into godin.com later.

---

## 1. Colors — CSS Variables

Use Divi's **CSS Variable** color-picker option wherever possible.

| Purpose | Variable name | Hex value | Use for |
|---|---|---|---|
| Primary brand | `--gdi-color-primary` | `#0d5c63` | Headings, primary buttons, footer background, icons |
| Accent | `--gdi-color-accent` | `#c17f24` | Sparingly — CTAs, highlights |
| Accent (light) | `--gdi-color-accent-light` | `#e0a84d` | Hover states on accent elements |
| Link | `--gdi-color-link` | `#0e8c96` | Text links |
| Link hover | `--gdi-color-link-hover` | `#0d5c63` | Link hover state |
| Body text | `--gdi-color-text` | `#1a1a1a` | Paragraph text, primary reading text |
| Secondary text | `--gdi-color-text-secondary` | `#444444` | Sub-headings, de-emphasized but still readable text |
| Muted text | `--gdi-color-text-muted` | `#777777` | Captions, our Blurb "See what fits." style lines |
| Border | `--gdi-color-border` | `#dddddd` | Dividers, card borders |
| Background | `--gdi-color-bg` | `#ffffff` | Default section background |
| Background alt | `--gdi-color-bg-alt` | `#f4f4f4` | Alternating section background |
| Footer bg | `--gdi-color-footer-bg` | `#0d5c63` | Footer only |
| Footer text | `--gdi-color-footer-text` | `#ffffff` | Footer only |

**Decision note:** neutrals are true gray, not teal-tinted — better legibility at body-text sizes than the teal-cast version we started with.

---

## 2. Typography

**Decision: one font family (Inter) for both headings and body**, differentiated by weight and size — not a serif/sans pairing. Reasoning: godin.com's Georgia-heading pairing reads as a fairly dated "corporate brochure" pattern, which works against godindev.com's direct, plain-spoken positioning ("Understand the business. Then build the software."). Inter is free, loads fast, and its wide weight range (300–800) carries hierarchy well on its own.

```css
--gdi-font-heading: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
--gdi-font-body:    'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
--gdi-weight-heading: 700;
--gdi-weight-body:    400;
```

**Action needed before this fully works:** Inter needs to actually be loaded as a webfont (it's not a system font) — either self-hosted or via Google Fonts `<link>`, added to the theme's `functions.php` enqueue. Flagged as a build task, not yet done.

### Heading Hierarchy

| Level | Use for | Divi field |
|---|---|---|
| **H1** | Page's single main headline — one per page, ever | Fullwidth Header → Title |
| **H2** | Major section headings within a page | Section-level Text/Heading modules |
| **H3** | Sub-section headings, Blurb module titles | Blurb → Title field |
| **H4** | Minor headings (e.g. individual case study names) | Text module, set heading level in Design tab |
| **H5/H6** | Rarely needed — small label-style text | Use sparingly |

**Rule of thumb:** never skip a level, never two H1s on one page.

---

## 3. Type Scale

7 steps — matches godin.com's range so both sites share a rhythm even where color/font choices differ.

| Token | Desktop | Tablet | Phone | Use for |
|---|---|---|---|---|
| `--gdi-text-3xl` | 3rem | 2.25rem | 1.85rem | Largest hero text, if ever needed beyond H1 |
| `--gdi-text-2xl` | 2.5rem | 2rem | 1.65rem | H1 |
| `--gdi-text-xl` | 1.75rem | 1.5rem | 1.35rem | H2 |
| `--gdi-text-lg` | 1.25rem | — | — | H3 / Blurb titles |
| `--gdi-text-base` | 1rem | — | — | Body copy |
| `--gdi-text-sm` | 0.875rem | — | — | Captions, muted text |
| `--gdi-text-xs` | 0.75rem | — | — | Fine print, form labels |

---

## 4. Spacing

6 steps.

| Token | Value | Use for |
|---|---|---|
| `--gdi-space-2xs` | 0.25rem | Icon-to-text tight gaps |
| `--gdi-space-xs` | 0.5rem | Small gaps |
| `--gdi-space-sm` | 1rem | Default gap between related elements |
| `--gdi-space-md` | 2rem | Gap between distinct content blocks |
| `--gdi-space-lg` | 4rem | Gap between major page sections |
| `--gdi-space-2xl` | 6rem | Large hero/section padding |

---

## 5. Layout, Shape & Motion

| Token | Value | Use for |
|---|---|---|
| `--gdi-max-width` | 1200px | Content max-width, matches godin.com |
| `--gdi-radius-sm` | 4px | Buttons, small cards |
| `--gdi-radius-md` | 8px | Blurb cards, images |
| `--gdi-radius-lg` | 16px | Large feature cards, modals |
| `--gdi-shadow-sm` | subtle | Cards at rest |
| `--gdi-shadow-md` | moderate | Cards on hover |
| `--gdi-shadow-lg` | pronounced | Modals, elevated elements |
| `--gdi-transition` | 0.2s ease-in-out | All hover/state transitions, for consistency |

---

## 6. Module-Specific Notes (living section)

**Blurb module (signposts, Home page):**
- Icon color: `--gdi-color-primary`
- Icon placement: Top, centered
- Title: H3 equivalent (`--gdi-text-lg`), weight 700
- Body text color: `--gdi-color-text-muted`
- Text alignment: Centered
- *(Once Inter is loaded, apply `--gdi-font-heading` to the title field explicitly — Divi may default to its own theme font otherwise.)*

**Fullwidth Header (Home hero):** not yet built.

**Blog module:** placeholder only on Home — remove when replacing with real content, not a styling reference.

---

## 7. File Structure (matches godin.com's pattern)

- `00-tokens.css` — pure token values only, no component rules
- `01-components.css` — component-level overrides (currently: nav centering fallback)
- Enqueued in that order via `functions.php`, both as dependencies of the main `style.css`

---

## 8. Candidates to port back into godin.com later

Once these are proven out on godindev.com:
- True-gray neutral scale (replacing whatever godin.com currently uses, if different)
- Full 7-step type scale and 6-step spacing scale
- Layout/shadow/radius/transition tokens (godin.com already has equivalents — worth diffing values once godindev's are finalized)
- Possibly the Inter typography decision, if it reads well in practice — though godin.com's Georgia heading may be worth keeping deliberately as that site's own distinct feel rather than assuming uniformity is always better

---

*Living document — update Section 6 as each page/module gets built.*
