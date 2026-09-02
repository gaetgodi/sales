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
// event.defaultPrevented). Not relied on here for two reasons: (1) it
// only animates when body carries an "et_smooth_scroll" class, which
// nothing on this site sets, so as configured it wouldn't deliver
// smooth scrolling regardless; (2) it doesn't account for the sticky
// header's height at all, so even when it does move the page the
// target heading lands underneath the header, not below it.
//
// This attaches its own handler in the capture phase — capture runs
// before Divi's bubble-phase delegated handler ever sees the event —
// and stopImmediatePropagation() so Divi's handler doesn't also run
// afterward. It computes the scroll target manually (window.scrollTo,
// not scrollIntoView) so the sticky header's *current* height —
// measured fresh via getBoundingClientRect() on every click, not a
// hardcoded pixel guess — can be subtracted from it.
//
// That height has to come from ".et-l--header .et_pb_section" (the
// actual Divi Section that gets position:fixed once its own sticky JS
// engages — confirmed directly: it carries et_pb_sticky/
// et_pb_sticky--top and measures 320px in both states), not from
// ".et-l--header" itself: that outer Theme Builder wrapper keeps a
// static 384px box even once its only child pops out to fixed
// positioning and scrolls away underneath it (top: -804 once scrolled
// — a placeholder height Divi leaves behind, presumably to avoid a
// layout jump), so measuring the wrapper overshoots the real visible
// header by 64px. That 64px is exactly what showed up as a visible gap
// with leftover previous-section text still in it once a jump-link
// landed — .et_pb_section is the fix.
//
// For a logged-in admin, #wpadminbar sits fixed above the sticky
// header (32px, confirmed via its own getBoundingClientRect()) and has
// to be added on top of the header's own height too, or the target
// lands that far underneath the header — which is exactly what a
// buffer-only fix (raising HEADER_CLEARANCE_BUFFER without accounting
// for the admin bar) would have gotten wrong: enough headroom to clear
// the header for a logged-in admin would be too much for a real,
// logged-out visitor, who has no admin bar at all. Measuring
// #wpadminbar's actual height (0 when it's absent, i.e. for every real
// visitor) keeps the offset correct for both.
//
// The header's own "sticky" setting is desktop-only (see
// getStickyHeaderHeight()'s own comment) — below that breakpoint it's
// never pinned, so the compensation has to be skipped there entirely,
// not just resized down, or it'd push the target under the page by the
// header's height for no reason on a viewport where nothing is
// covering it. A small extra buffer keeps the heading from landing
// flush against the header's bottom edge. Respects
// prefers-reduced-motion the same way the CSS scroll-behavior override
// does (01-components.css).
function divi_sales_child_nav_jump_links() {
	?>
	<script>
	(function () {
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var HEADER_CLEARANCE_BUFFER = 16; // breathing room below the header, beyond its own height

		function getStickyHeaderHeight() {
			// The header's own Theme Builder "sticky" setting is
			// desktop-only — its stored config is explicit:
			// {"desktop":{"position":"top"},"tablet":{"position":"none"},
			// "phone":{"position":"none"}}. Below that breakpoint (Divi's
			// own tablet cutoff, matching --gdi-bp-tablet in
			// 00-tokens.css) the header scrolls away normally with the
			// rest of the page, so there's no pinned overlap to clear —
			// applying this compensation there would push the target
			// down by the header's height for no reason.
			if ( window.innerWidth <= 980 ) {
				return 0;
			}
			var section = document.querySelector( '.et-l--header .et_pb_section' );
			var height = section ? Math.ceil( section.getBoundingClientRect().height ) : 0;
			var adminBar = document.getElementById( 'wpadminbar' );
			if ( adminBar ) {
				height += Math.ceil( adminBar.getBoundingClientRect().height );
			}
			return height;
		}

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
			var offset = getStickyHeaderHeight() + HEADER_CLEARANCE_BUFFER;
			var targetTop = target.getBoundingClientRect().top + window.pageYOffset - offset;
			window.scrollTo( { top: Math.max( 0, targetTop ), behavior: reduceMotion ? 'auto' : 'smooth' } );
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

// Custom mobile nav drawer — replaces Divi's native mobile
// hamburger/dropdown entirely (see the "Divi's native mobile
// hamburger/dropdown ... is retired" comment in 01-components.css for
// the full history: three separate confirmed-real bugs fixed there in
// turn — a width collapse, then two different ancestor
// overflow:hidden clips — and the dropdown still wasn't reliably
// landing on a non-zero height on a real device. The remaining piece
// was Divi's own JS-computed slideDown/slideUp height animation
// itself getting stuck, not anything left in our CSS or markup by
// that point.
//
// Rather than keep chasing Divi's mechanism, this bypasses it: a
// right-side slide-in panel driven by one class on <body> plus a
// plain CSS position/transition (01-components.css, "Custom mobile
// nav drawer") — nothing here is ever "mid-calculation" the way a
// JS-computed height can be stuck partway. Loosely follows the same
// drawer pattern already proven on pickleballstouffville.ca
// (github.com/gaetgodi/SPP, css/spp-drawers.css + js/spp-drawers.js),
// simplified since this menu is flat (no accordion/submenu needed for
// 6 top-level items) and restyled to this site's own tokens instead of
// SPP's palette.
//
// The panel's items are built by cloning the real primary menu's own
// top-level links (".et_pb_menu__menu > nav > ul > li") rather than
// hardcoding labels/URLs here, so it can't silently drift out of sync
// if the menu is ever edited in wp-admin. If that selector finds
// nothing (menu markup changed, or this ran before Divi's own menu
// script populated it), the whole drawer is skipped rather than
// inserted empty — Divi's native hamburger is only ever hidden via
// CSS, not removed from the DOM, so there's no dead-end state where
// mobile visitors have no menu at all.
function divi_sales_child_mobile_nav() {
	?>
	<script>
	(function () {
		var desktopItems = document.querySelectorAll( '.et_pb_menu_0_tb_header .et_pb_menu__menu > nav > ul > li' );
		if ( ! desktopItems.length ) {
			return;
		}

		var overlay = document.createElement( 'div' );
		overlay.id = 'gdi-mm-overlay';

		var openBtn = document.createElement( 'button' );
		openBtn.id = 'gdi-mm-open';
		openBtn.type = 'button';
		openBtn.setAttribute( 'aria-label', 'Open menu' );
		openBtn.setAttribute( 'aria-expanded', 'false' );
		openBtn.setAttribute( 'aria-controls', 'gdi-mm-panel' );
		var openIcon = document.createElement( 'span' );
		openIcon.className = 'gdi-mm-open-icon';
		openIcon.setAttribute( 'aria-hidden', 'true' );
		openBtn.appendChild( openIcon );

		var panel = document.createElement( 'nav' );
		panel.id = 'gdi-mm-panel';
		panel.setAttribute( 'aria-label', 'Mobile navigation' );

		var panelHeader = document.createElement( 'div' );
		panelHeader.className = 'gdi-mm-panel-header';
		var panelTitle = document.createElement( 'span' );
		panelTitle.className = 'gdi-mm-panel-title';
		panelTitle.textContent = 'Menu';
		var closeBtn = document.createElement( 'button' );
		closeBtn.id = 'gdi-mm-close';
		closeBtn.type = 'button';
		closeBtn.setAttribute( 'aria-label', 'Close menu' );
		var closeIcon = document.createElement( 'span' );
		closeIcon.className = 'gdi-mm-close-icon';
		closeIcon.setAttribute( 'aria-hidden', 'true' );
		closeBtn.appendChild( closeIcon );
		panelHeader.appendChild( panelTitle );
		panelHeader.appendChild( closeBtn );

		var list = document.createElement( 'ul' );
		list.className = 'gdi-mm-list';
		desktopItems.forEach( function ( li ) {
			var a = li.querySelector( 'a' );
			if ( ! a ) {
				return;
			}
			var item = document.createElement( 'li' );
			if ( li.classList.contains( 'current-menu-item' ) ) {
				item.className = 'current-menu-item';
			}
			var link = document.createElement( 'a' );
			link.href = a.getAttribute( 'href' );
			link.textContent = a.textContent.trim();
			item.appendChild( link );
			list.appendChild( item );
		} );

		panel.appendChild( panelHeader );
		panel.appendChild( list );

		// The open button is appended into .et-l--header itself, not
		// <body> — it needs to be a real descendant for its own
		// position:absolute (01-components.css) to anchor against the
		// header card's own edge rather than the viewport. Falls back
		// to <body> (viewport-fixed positioning would be wrong, but a
		// misplaced button beats no button at all) only if that
		// wrapper somehow isn't there.
		var headerEl = document.querySelector( '.et-l--header' ) || document.body;
		headerEl.appendChild( openBtn );
		document.body.appendChild( overlay );
		document.body.appendChild( panel );

		function closeMenu() {
			document.body.classList.remove( 'gdi-mm-open' );
			openBtn.setAttribute( 'aria-expanded', 'false' );
		}

		function toggleMenu() {
			if ( document.body.classList.contains( 'gdi-mm-open' ) ) {
				closeMenu();
			} else {
				document.body.classList.add( 'gdi-mm-open' );
				openBtn.setAttribute( 'aria-expanded', 'true' );
			}
		}

		openBtn.addEventListener( 'click', toggleMenu );
		closeBtn.addEventListener( 'click', closeMenu );
		overlay.addEventListener( 'click', closeMenu );
		list.addEventListener( 'click', function ( e ) {
			if ( e.target.tagName === 'A' ) {
				closeMenu();
			}
		} );

		// Click-outside-closes, matching the reference drawer's own
		// pattern — guarded so it only ever acts while open, and never
		// fights the open button's own click (which toggleMenu already
		// handles).
		document.addEventListener( 'click', function ( e ) {
			if ( ! document.body.classList.contains( 'gdi-mm-open' ) ) {
				return;
			}
			if ( e.target.closest( '#gdi-mm-panel' ) || e.target.closest( '#gdi-mm-open' ) ) {
				return;
			}
			closeMenu();
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				closeMenu();
			}
		} );
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'divi_sales_child_mobile_nav' );

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
