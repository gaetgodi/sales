<?php
function divi_sales_child_enqueue_styles() {
    wp_enqueue_style( "divi-parent-style", get_template_directory_uri() . "/style.css" );
    wp_enqueue_style( "godindev-fonts",
        get_stylesheet_directory_uri() . "/00-fonts.css",
        array( "divi-parent-style" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "godindev-tokens",
        get_stylesheet_directory_uri() . "/00-tokens.css",
        array( "godindev-fonts" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "godindev-components",
        get_stylesheet_directory_uri() . "/01-components.css",
        array( "godindev-tokens" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "godindev-typography",
        get_stylesheet_directory_uri() . "/02-typography.css",
        array( "godindev-components" ),
        wp_get_theme()->get("Version")
    );
    wp_enqueue_style( "divi-sales-child-style",
        get_stylesheet_directory_uri() . "/style.css",
        array( "divi-parent-style", "godindev-typography" ),
        wp_get_theme()->get("Version")
    );
}
add_action( "wp_enqueue_scripts", "divi_sales_child_enqueue_styles" );

// Explicit preload hint for Newsreader — the browser's own font-preload
// scanner was found to resolve .gdi-header-tagline's font-family using a
// stale pre-specificity-fix state (Inter, weight 300 italic) instead of
// the actual final cascade result (Newsreader), so it never triggered a
// fetch for the real font on its own; document.fonts.load() proved the
// file itself, server response, and CSS were all correct. A preload hint
// bypasses that scanner's guesswork by declaring the resource directly.
function divi_sales_child_preload_fonts() {
    echo '<link rel="preload" href="' . esc_url( get_stylesheet_directory_uri() . "/fonts/Newsreader-Italic.woff2" ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( "wp_head", "divi_sales_child_preload_fonts", 1 );

// Google Analytics 4 (gtag.js) — Measurement ID G-7QQEBFR63Q. Standard
// gtag.js snippet, unmodified, output verbatim rather than split across
// wp_enqueue_script calls so it stays byte-for-byte what Google's own
// GA4 setup UI hands out (easier to diff against future re-copies from
// Google, and avoids any escaping surprises from routing an inline
// dataLayer.push snippet through wp_add_inline_script). This is also
// what the tagline rotation script's "typeof gtag === 'function'" guard
// (below) was written against — before this hook existed, that guard
// was a confirmed no-op site-wide.
function divi_sales_child_ga4() {
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7QQEBFR63Q"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-7QQEBFR63Q');
    </script>
    <?php
}
add_action( "wp_head", "divi_sales_child_ga4" );

// Rotating tagline — picks one of four strings at random on each page load
// and swaps it into the tagline's actual text node. Targets
// ".gdi-header-tagline .et_pb_text_inner p" rather than the module wrapper
// alone: per the specificity/selector investigation documented on the CSS
// rule for this class (01-components.css), Divi wraps Text-module content
// two levels deep, and the visible text lives on that nested <p>, not the
// wrapper div — so that's also the node whose textContent actually needs
// to change. querySelectorAll (not querySelector) so this fires correctly
// on every page the tagline module is placed on, not just Home, and covers
// the (unlikely but possible) case of more than one instance on a page —
// all instances get the same chosen variant rather than independently
// randomized text. Runs from wp_footer, after the DOM it targets exists,
// unlike the wp_head font-preload hook above.
//
// GA4 dependency: this also fires a gtag() 'tagline_shown' event with the
// chosen variant so the copy can eventually be compared. GA4 (see
// divi_sales_child_ga4() above, also on wp_head, registered first) now
// provides window.gtag, so the typeof-guard below succeeds and the event
// actually reaches GA4 — kept as a guard rather than assumed, so this
// script still degrades gracefully (rotation only, no throw) if GA4 is
// ever removed or fails to load.
function divi_sales_child_rotate_tagline() {
    ?>
    <script>
    (function () {
        var taglines = [
            "Understand the business. Then build the software.",
            "The software follows the business, not the other way around.",
            "Know how you work. Then build what helps.",
            "Built after understanding, not before."
        ];

        function applyRotatingTagline() {
            var nodes = document.querySelectorAll( ".gdi-header-tagline .et_pb_text_inner p" );
            if ( ! nodes.length ) {
                return;
            }
            var index = Math.floor( Math.random() * taglines.length );
            var variant = "variant_" + ( index + 1 );
            nodes.forEach( function ( node ) {
                node.textContent = taglines[ index ];
            } );
            if ( typeof gtag === "function" ) {
                gtag( "event", "tagline_shown", { variant: variant } );
            }
        }

        if ( document.readyState === "loading" ) {
            document.addEventListener( "DOMContentLoaded", applyRotatingTagline );
        } else {
            applyRotatingTagline();
        }
    })();
    </script>
    <?php
}
add_action( "wp_footer", "divi_sales_child_rotate_tagline" );
