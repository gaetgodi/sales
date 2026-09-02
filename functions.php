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
// chosen variant so the copy can eventually be compared. GA4 is now
// injected by Site Kit's own tag snippet (wp_head, ahead of this
// wp_footer hook) rather than a hardcoded gtag.js block in this theme —
// Site Kit's snippet defines window.gtag the same way, so the
// typeof-guard below still succeeds. Kept as a guard rather than
// assumed, so this script still degrades gracefully (rotation only, no
// throw) if GA4 is ever disconnected or fails to load.
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

// Secondary-nav jump links (.gdi-services-nav, on /services/ and
// /work/) — Divi 5 has its own site-wide smooth-scroll handler for any
// in-page "#anchor" link (script-library-frontend-global-functions.js,
// window.et_pb_smooth_scroll), and it does intercept clicks on these
// links (preventDefault + stopPropagation, confirmed via
// event.defaultPrevented). Two reasons not to just rely on it here:
// 1) it only animates (rather than jumping instantly) when the body
//    carries an "et_smooth_scroll" class, which isn't set anywhere on
//    this site — so as configured it wouldn't deliver the smooth
//    scroll asked for regardless. 2) automated testing in this session
//    repeatedly saw its jQuery animate({scrollTop}) — and even a plain
//    scrollIntoView() — stall partway on a backgrounded tab
//    (document.hidden === true throttles requestAnimationFrame, a
//    standard browser behavior, not specific to this site); a direct,
//    non-animated scrollTo() always completed correctly in the same
//    tab, isolating the stall to animation-under-automation rather
//    than a logic bug — but it means the animated case couldn't be
//    fully verified end-to-end here and is worth a real click-through.
// This attaches its own handler in the capture phase — capture runs
// before Divi's bubble-phase delegated handler ever sees the event —
// and stopImmediatePropagation() so Divi's handler doesn't also run
// afterward. Respects prefers-reduced-motion the same way the CSS
// scroll-behavior override does (01-components.css).
function divi_sales_child_nav_jump_links() {
	?>
	<script>
	(function () {
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function jumpToSection( e ) {
			var id = this.getAttribute( 'href' ).slice( 1 );
			var target = document.getElementById( id );
			if ( ! target ) {
				return;
			}
			e.preventDefault();
			if ( e.stopImmediatePropagation ) {
				e.stopImmediatePropagation();
			}
			target.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' } );
			if ( window.history && window.history.pushState ) {
				history.pushState( null, '', '#' + id );
			}
		}

		document.querySelectorAll( '.gdi-services-nav a' ).forEach( function ( a ) {
			a.addEventListener( 'click', jumpToSection, true );
		} );
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'divi_sales_child_nav_jump_links' );

// Footer contact form — name/email/message, independent of the Contact
// page's own (currently placeholder) form. No form-builder plugin: the
// site was assumed to have Fluent Forms installed and configured, but
// only FluentSMTP/CRM/Boards are actually present — Fluent Forms itself
// isn't. Rather than install a new plugin unasked, this reuses the
// FluentSMTP -> Brevo pipeline that's already live via plain wp_mail().
//
// The form's actual markup (labels, fields, button — everything an
// editor would want to see or tweak) lives directly, visibly, in a Divi
// Code module in the footer's Theme Builder layout: a first version
// instead put the *entire* form behind a single opaque
// "<!--GDI_FOOTER_CONTACT_FORM-->" placeholder comment, which meant the
// module showed as just that raw comment in the Divi Builder — not
// editable in any meaningful sense. Only the two genuinely
// per-request-dynamic pieces stay as small inline markers this filter
// swaps out: the CSRF nonce ("<!--GDI_NONCE-->", inside the hidden
// input's value attribute) and the post-submit status notice
// ("<!--GDI_FORM_NOTICE-->", after the submit button) — the same kind of
// exception the footer's own copyright module already makes for its
// dynamic-content current-year variable.
//
// A nonce embedded in the *stored* block content directly (no filter)
// would go stale within a day; WP Super Cache (30 min TTL here) would
// then serve that stale nonce to every visitor until the cache expired.
// Swapping it in on every uncached render keeps only Super Cache's
// 30-minute TTL — well inside the nonce's ~24h validity window — between
// a page load and a working form.
function divi_sales_child_footer_contact_form_notice() {
	$status = isset( $_GET['gdi_contact'] ) ? sanitize_key( wp_unslash( $_GET['gdi_contact'] ) ) : '';
	if ( 'sent' === $status ) {
		return '<p class="gdi-footer-form-notice gdi-footer-form-notice--success" role="status">Thanks — your message has been sent.</p>';
	}
	if ( 'error' === $status ) {
		return '<p class="gdi-footer-form-notice gdi-footer-form-notice--error" role="alert">Something went wrong — please check your details and try again.</p>';
	}
	return '';
}

function divi_sales_child_inject_footer_contact_form( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( false !== strpos( $block_content, '<!--GDI_NONCE-->' ) ) {
		$block_content = str_replace( '<!--GDI_NONCE-->', esc_attr( wp_create_nonce( 'gdi_footer_contact' ) ), $block_content );
	}
	if ( false !== strpos( $block_content, '<!--GDI_FORM_NOTICE-->' ) ) {
		$block_content = str_replace( '<!--GDI_FORM_NOTICE-->', divi_sales_child_footer_contact_form_notice(), $block_content );
	}
	return $block_content;
}
add_filter( 'render_block', 'divi_sales_child_inject_footer_contact_form', 10, 2 );

function divi_sales_child_handle_footer_contact_form() {
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}
	$redirect = remove_query_arg( 'gdi_contact', $redirect ) . '#gdi-footer-contact';

	if (
		! isset( $_POST['gdi_footer_contact_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gdi_footer_contact_nonce'] ) ), 'gdi_footer_contact' )
	) {
		wp_safe_redirect( esc_url_raw( add_query_arg( 'gdi_contact', 'error', $redirect ) ) );
		exit;
	}

	// Honeypot: real visitors never see or fill this field. Report success
	// to the bot without actually sending mail, rather than tipping it off.
	if ( ! empty( $_POST['gdi_website'] ) ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( 'gdi_contact', 'sent', $redirect ) ) );
		exit;
	}

	$name    = isset( $_POST['gdi_name'] ) ? sanitize_text_field( wp_unslash( $_POST['gdi_name'] ) ) : '';
	$email   = isset( $_POST['gdi_email'] ) ? sanitize_email( wp_unslash( $_POST['gdi_email'] ) ) : '';
	$message = isset( $_POST['gdi_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gdi_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( 'gdi_contact', 'error', $redirect ) ) );
		exit;
	}

	// Recipient: sales@godindev.com, the address FluentSMTP is already
	// configured to send site mail through/as (see its Brevo connection),
	// rather than the unrelated site admin_email.
	$to      = 'sales@godindev.com';
	$subject = sprintf( '[godindev.com footer form] Message from %s', $name );
	$body    = "Name: $name\nEmail: $email\n\nMessage:\n$message";
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $subject, $body, $headers );

	wp_safe_redirect( esc_url_raw( add_query_arg( 'gdi_contact', $sent ? 'sent' : 'error', $redirect ) ) );
	exit;
}
add_action( 'admin_post_gdi_footer_contact', 'divi_sales_child_handle_footer_contact_form' );
add_action( 'admin_post_nopriv_gdi_footer_contact', 'divi_sales_child_handle_footer_contact_form' );

// Pending case studies (currently: the BIAO write-up on /work/, awaiting
// the organization's sign-off before it goes public with real specifics
// about them). The module itself is real, stored, visually-editable
// Divi content — wrapped in a ".gdi-case-study--pending" div — but this
// filter strips that whole block from the rendered output for anyone
// without manage_options, so it's completely absent from the page for
// every public visitor: not just unlinked, not just hidden by CSS,
// genuinely never in the HTML that goes out. Logged-in admins (which
// includes the Divi Builder canvas, and a normal logged-in visit to the
// live URL) still see it, dashed-border-flagged as a draft (01-
// components.css) so it can't be mistaken for finished, live content.
// WP Super Cache doesn't cache logged-in views (its own "disabled for
// logged-in visitors" default), so this always evaluates fresh for an
// admin rather than risking a stale cached admin-view.
//
// To publish once sign-off is in: open the block in the Divi Builder
// and remove the "gdi-case-study--pending" class from its wrapping div
// (Text module, Text tab) — that's the only thing gating it, no code
// change needed.
function divi_sales_child_hide_pending_case_studies( $block_content, $block ) {
	if ( false === strpos( $block_content, 'gdi-case-study--pending' ) ) {
		return $block_content;
	}
	return current_user_can( 'manage_options' ) ? $block_content : '';
}
add_filter( 'render_block', 'divi_sales_child_hide_pending_case_studies', 10, 2 );

// Same pending-case-study gate, applied to one link inside /work/'s
// secondary nav rather than a whole block: the nav's four links (in
// order: Outfitter, Stouffville, Recipes, BIAO) live together in one
// Code module, so the whole-block filter above — which removes an
// entire block — can't be reused as-is here without also hiding the
// three links that ARE public. Instead the BIAO link alone is wrapped
// in "<!--GDI_PENDING_LINK_START-->...<!--GDI_PENDING_LINK_END-->"
// markers in that Code module's own content, and this filter strips
// everything between them (markers included) for anyone without
// manage_options — same capability check, same never-in-the-HTML
// guarantee as the case-study block itself, just scoped to a substring
// instead of the whole block. For an admin it just drops the markers
// and leaves the link.
function divi_sales_child_hide_pending_nav_link( $block_content, $block ) {
	if ( false === strpos( $block_content, '<!--GDI_PENDING_LINK_START-->' ) ) {
		return $block_content;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return str_replace( array( '<!--GDI_PENDING_LINK_START-->', '<!--GDI_PENDING_LINK_END-->' ), '', $block_content );
	}
	return preg_replace( '/<!--GDI_PENDING_LINK_START-->.*?<!--GDI_PENDING_LINK_END-->/s', '', $block_content );
}
add_filter( 'render_block', 'divi_sales_child_hide_pending_nav_link', 10, 2 );
