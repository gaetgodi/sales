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

// Footer contact form — name/email/message, independent of the Contact
// page's own (currently placeholder) form. No form-builder plugin: the
// site was assumed to have Fluent Forms installed and configured, but
// only FluentSMTP/CRM/Boards are actually present — Fluent Forms itself
// isn't. Rather than install a new plugin unasked, this reuses the
// FluentSMTP -> Brevo pipeline that's already live via plain wp_mail().
//
// The footer's Theme Builder layout (a WP database record, not a theme
// file) embeds a literal "<!--GDI_FOOTER_CONTACT_FORM-->" marker comment
// inside a Divi Code module. A static form baked directly into that
// stored block content would carry one fixed nonce forever — fine at
// first, stale within a day, and WP Super Cache (30 min TTL here) would
// then serve that stale nonce to every visitor until the cache expired.
// Hooking core's render_block filter instead swaps the marker for a
// freshly rendered form (fresh nonce) on every uncached page render, so
// only WP Super Cache's normal 30-minute TTL — well inside the nonce's
// ~24h validity window — sits between a page load and a working form.
function divi_sales_child_footer_contact_form_markup() {
	$status = isset( $_GET['gdi_contact'] ) ? sanitize_key( wp_unslash( $_GET['gdi_contact'] ) ) : '';
	ob_start();
	?>
	<div id="gdi-footer-contact" class="gdi-footer-form-wrap">
		<h2 class="gdi-footer-form-heading">Get in touch</h2>
		<form class="gdi-footer-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gdi_footer_contact">
			<?php wp_nonce_field( 'gdi_footer_contact', 'gdi_footer_contact_nonce' ); ?>
			<div class="gdi-footer-form-hp">
				<label for="gdi_website">Website</label>
				<input type="text" id="gdi_website" name="gdi_website" tabindex="-1" autocomplete="off">
			</div>
			<div class="gdi-footer-form-row">
				<div class="gdi-footer-form-field">
					<label for="gdi_name">Name</label>
					<input type="text" id="gdi_name" name="gdi_name" placeholder="Name" required>
				</div>
				<div class="gdi-footer-form-field">
					<label for="gdi_email">Email</label>
					<input type="email" id="gdi_email" name="gdi_email" placeholder="Email" required>
				</div>
			</div>
			<div class="gdi-footer-form-field">
				<label for="gdi_message">Message</label>
				<textarea id="gdi_message" name="gdi_message" placeholder="Message" rows="3" required></textarea>
			</div>
			<button type="submit" class="gdi-footer-form-submit">Send</button>
			<?php if ( 'sent' === $status ) : ?>
				<p class="gdi-footer-form-notice gdi-footer-form-notice--success" role="status">Thanks — your message has been sent.</p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="gdi-footer-form-notice gdi-footer-form-notice--error" role="alert">Something went wrong — please check your details and try again.</p>
			<?php endif; ?>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

function divi_sales_child_inject_footer_contact_form( $block_content, $block ) {
	if ( is_admin() || false === strpos( $block_content, '<!--GDI_FOOTER_CONTACT_FORM-->' ) ) {
		return $block_content;
	}
	return str_replace( '<!--GDI_FOOTER_CONTACT_FORM-->', divi_sales_child_footer_contact_form_markup(), $block_content );
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
