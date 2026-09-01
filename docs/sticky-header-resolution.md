# Sticky header — resolved

**Status: working correctly, resolved in the Divi builder — not via CSS.**

## Final approach

Divi's own native **Sticky Position** setting on the header Section, applied directly in the builder (Design tab → Sticky), including its built-in tablet/phone responsive controls to disable stickiness below a certain breakpoint. Applied identically on the Home page template and the shared header used elsewhere, so behavior doesn't diverge between pages.

**No custom CSS `position: sticky` rule is in use for the header anymore.** If you find one in `01-components.css`, it's dead/historical — safe to remove, not something to debug.

## Why the earlier attempts failed (for context, not action)

An earlier session tried building sticky manually via CSS (`position: sticky` on `#gli-header`, later moved to `.et-l--header`) instead of using Divi's native setting. That approach hit two real, confirmed problems before being abandoned:

1. **Insufficient containing-block height** — the sticky element's ancestor had no extra height for the element to "travel" within, so sticky had nowhere to go.
2. **`overflow: hidden` on the wrong ancestor** — clipped the sticky element's ability to escape its own box.

Divi's own native sticky mechanism doesn't have these problems, since it's not relying on the same DOM/CSS relationship. Once the CSS was cleaned up (the `#gli-header` ID fully retired, everything consolidated onto `.et-l--header`, matching how `.et-l--footer` already worked — see the header/footer consolidation notes), the underlying structure was clean enough that Divi's native setting worked correctly on the first real attempt.

**Lesson for future work:** when Divi offers a native builder setting for something (sticky, gradients, etc.), prefer it over a hand-rolled CSS equivalent unless there's a specific, confirmed reason the native option can't do what's needed. The custom CSS route here cost a very long investigation session for something Divi's own UI handled cleanly once the CSS around it was tidy.
