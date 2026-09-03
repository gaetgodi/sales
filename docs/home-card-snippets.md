# Home Page — Card Code Module Snippets

Paste each into a Code module (replacing the old Blurb module in the same position), matching the row order: Services/Work/About in row 1, Event Photography/Contact in row 2.

Each card is a real `<a>` anchor (not a Blurb module's JS-only click handler — see 01-components.css's `.gdi-card` comment for why), styled by `.gdi-card` in `01-components.css`. The `.gdi-card-cta` line at the end of each is the amber "click to explore" cue — same accent color as `.gdi-service-cta` elsewhere on the site, but plain text rather than a button, since the whole card is already the clickable target.

## Services
```html
<a href="/services/" class="gdi-card">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
  <h3>Looking for custom development, a legacy site migration, or a ready-to-go recipe platform?</h3>
  <p>From free-discovery custom builds to migrating an aging site — plus tools available as add-ons or for licensing on their own. See what fits your situation.</p>
  <span class="gdi-card-cta">Explore Services →</span>
</a>
```

## Work
```html
<a href="/work/" class="gdi-card">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
  <h3>Want proof, not promises?</h3>
  <p>Real projects for real clients — a system that's run for ten years, a full site migration, an active pickleball club platform, and more. See what's actually been built.</p>
  <span class="gdi-card-cta">See the Work →</span>
</a>
```

## About
```html
<a href="/about/" class="gdi-card">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>
  <h3>Curious how this actually works?</h3>
  <p>Decades of programming experience and a research background, built on one simple idea: understand the business first, then build the software.</p>
  <span class="gdi-card-cta">Read the Approach →</span>
</a>
```

## Event Photography
```html
<a href="/event-photography/" class="gdi-card">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 15.2c1.77 0 3.2-1.43 3.2-3.2s-1.43-3.2-3.2-3.2-3.2 1.43-3.2 3.2 1.43 3.2 3.2 3.2zM9 2 7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg>
  <h3>Need a photographer for your next event?</h3>
  <p>A photo booth with live background effects, real group photos, and a gallery that makes finding your shots easy afterward — see it in action from a real reunion.</p>
  <span class="gdi-card-cta">See Event Photography →</span>
</a>
```

## Contact
```html
<a href="/contact/" class="gdi-card">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
  <h3>Ready to talk?</h3>
  <p>No plan, no proposal, no invoice — just a conversation to see if it's a good fit. I read and reply to every message myself.</p>
  <span class="gdi-card-cta">Get in Touch →</span>
</a>
```

## Hero intro (above the card grid)

Added 2026-09-03 — a short, warm intro sits above the card grid now (native Divi Heading + Text modules, not Code/HTML — no anchor/click concerns here since it isn't a link). Deliberately kept short and non-repetitive of the site header's own rotating tagline (`.gdi-header-tagline`, four variants including "Understand the business. Then build the software." — see functions.php/header layout), which already carries the positioning statement on every page:

- Heading (H2, `--gdi-color-primary`): "Take a look around."
- Text (`--gdi-color-text-muted`): "Whatever brought you here — a project, a question, or just curiosity — pick whichever of these fits, and dig in."
