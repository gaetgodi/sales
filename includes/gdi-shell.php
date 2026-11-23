<?php
/**
 * Page shell helpers for the Divi Sales child theme (godindev.com), redesign foundation.
 *
 * - Inline head script that applies the saved colour theme before first paint.
 * - Footer sitemap menu location.
 * - Mobile drawer markup, rendered from the primary WordPress menu on EVERY page
 *   (so Home gets a phone menu even though its header has no Menu module).
 * - Theme picker markup, skip link, and the [gdi_breadcrumbs], [gdi_subnav],
 *   [gdi_footer_sitemap] and [gdi_theme_picker] shortcodes.
 *
 * Behaviour (drawer, picker) lives in js/gdi-shell.js; styling in 04-shell.css.
 * Nothing here reads or writes the database beyond reading menus and pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True while the Divi Visual Builder is rendering the page. The shell is not injected there.
 */
function gdi_shell_is_builder() {
	return function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();
}

/**
 * Colour themes: allowed values for the visitor's saved choice (browser localStorage key "gdi-theme").
 */
function gdi_shell_theme_choices() {
	return array(
		'brand'     => 'Brand',
		'parchment' => 'Warm Parchment',
		'slate'     => 'Dark Slate',
		'system'    => 'System',
	);
}

/**
 * Applies the saved theme before first paint. Printed at wp_head priority 1, ahead of the
 * stylesheets (priority 8). It reads only the visitor's own browser storage, so the cached
 * page HTML is identical for everyone and a page cache cannot serve the wrong theme.
 * No saved value, blocked storage or JavaScript off all mean Brand (the :root default).
 * Skipped inside the Divi Visual Builder (URL contains et_fb).
 */
function gdi_shell_theme_head_script() {
	if ( gdi_shell_is_builder() ) {
		return;
	}
	?>
<script>
(function () {
	try {
		if ( /[?&]et_fb=/.test( window.location.search ) ) { return; }
		var root = document.documentElement;
		var allowed = [ 'brand', 'parchment', 'slate', 'system' ];
		var choice = 'brand';
		try {
			var saved = window.localStorage.getItem( 'gdi-theme' );
			if ( allowed.indexOf( saved ) !== -1 ) { choice = saved; }
		} catch ( e ) {}
		var theme = choice;
		if ( choice === 'system' ) {
			theme = ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches ) ? 'slate' : 'brand';
		}
		root.setAttribute( 'data-gdi-choice', choice );
		root.setAttribute( 'data-gdi-theme', theme );
	} catch ( e ) {}
})();
</script>
	<?php
}
add_action( 'wp_head', 'gdi_shell_theme_head_script', 1 );

/**
 * Footer sitemap menu location. The primary menu location ("primary-menu") is Divi's own.
 */
function gdi_shell_register_menus() {
	register_nav_menus(
		array(
			'gdi-footer-sitemap' => __( 'Footer sitemap (top-level items become columns)', 'divi-sales-child' ),
		)
	);
}
add_action( 'after_setup_theme', 'gdi_shell_register_menus' );

/**
 * Skip link, first focusable element on the page.
 */
function gdi_shell_skip_link() {
	if ( gdi_shell_is_builder() ) {
		return;
	}
	echo '<a class="gdi-skip-link" href="#main-content">' . esc_html__( 'Skip to content', 'divi-sales-child' ) . '</a>' . "\n";
}
add_action( 'wp_body_open', 'gdi_shell_skip_link' );

/**
 * Theme picker markup: four native radio inputs in a fieldset (arrow-key navigation and ARIA for
 * free). Hidden until js/gdi-shell.js runs; every instance gets its own radio group name.
 */
function gdi_shell_theme_picker_html() {
	static $instance = 0;
	++$instance;
	$name = 'gdi-theme-' . $instance;

	$html  = '<fieldset class="gdi-theme-picker" hidden>';
	$html .= '<legend>' . esc_html__( 'Appearance', 'divi-sales-child' ) . '</legend>';
	$html .= '<div class="gdi-theme-options">';
	foreach ( gdi_shell_theme_choices() as $value => $label ) {
		$html .= '<label class="gdi-theme-option gdi-theme-option--' . esc_attr( $value ) . '">';
		$html .= '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		$html .= '<span class="gdi-theme-swatch" aria-hidden="true"><i></i><i></i><i></i></span>';
		$html .= '<span class="gdi-theme-label">' . esc_html( $label ) . '</span>';
		$html .= '</label>';
	}
	$html .= '</div></fieldset>';
	return $html;
}

/**
 * Mobile drawer: button, overlay and panel, rendered from the primary WordPress menu.
 * The panel starts inert; js/gdi-shell.js toggles inert/aria state and moves the button into
 * the header. Without JavaScript the button stays hidden and the footer sitemap carries the links.
 * With no primary menu assigned, neither the button nor the drawer is rendered.
 */
function gdi_shell_render_drawer() {
	if ( gdi_shell_is_builder() ) {
		return;
	}
	$menu = wp_nav_menu(
		array(
			'theme_location' => 'primary-menu',
			'container'      => false,
			'menu_class'     => 'gdi-mm-list',
			'menu_id'        => 'gdi-mm-menu',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
		)
	);
	if ( ! $menu ) {
		// No menu is assigned to the primary location: no menu button and no drawer navigation.
		// The picker stays available: the script copies it from this inert template into the footer.
		echo '<template id="gdi-picker-template">' . gdi_shell_theme_picker_html() . '</template>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the function.
		return;
	}
	?>
	<button type="button" id="gdi-mm-open" hidden aria-label="<?php echo esc_attr__( 'Open menu', 'divi-sales-child' ); ?>" aria-expanded="false" aria-controls="gdi-mm-panel"><span class="gdi-mm-open-icon" aria-hidden="true"></span></button>
	<div id="gdi-mm-overlay" aria-hidden="true"></div>
	<nav id="gdi-mm-panel" aria-label="<?php echo esc_attr__( 'Mobile navigation', 'divi-sales-child' ); ?>" inert>
		<div class="gdi-mm-panel-header">
			<span class="gdi-mm-panel-title"><?php echo esc_html__( 'Menu', 'divi-sales-child' ); ?></span>
			<button type="button" id="gdi-mm-close" aria-label="<?php echo esc_attr__( 'Close menu', 'divi-sales-child' ); ?>"><span class="gdi-mm-close-icon" aria-hidden="true"></span></button>
		</div>
		<?php echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nav_menu() output. ?>
		<div class="gdi-mm-panel-appearance">
			<?php echo gdi_shell_theme_picker_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the function. ?>
		</div>
	</nav>
	<?php
}
add_action( 'wp_footer', 'gdi_shell_render_drawer', 5 );

/**
 * [gdi_theme_picker] — the picker control, for the footer or header layout.
 */
function gdi_shell_theme_picker_shortcode() {
	return gdi_shell_theme_picker_html();
}
add_shortcode( 'gdi_theme_picker', 'gdi_shell_theme_picker_shortcode' );

/**
 * [gdi_breadcrumbs] — Home > ancestors > current page. Empty on the front page and 404.
 */
function gdi_shell_breadcrumbs_shortcode() {
	if ( is_front_page() || is_404() ) {
		return '';
	}

	$items = array(
		array( __( 'Home', 'divi-sales-child' ), home_url( '/' ) ),
	);

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return '';
		}
		if ( is_page() ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
				$items[] = array( get_the_title( $ancestor_id ), get_permalink( $ancestor_id ) );
			}
		} else {
			$blog_id = (int) get_option( 'page_for_posts' );
			if ( $blog_id && 'post' === $post->post_type ) {
				$items[] = array( get_the_title( $blog_id ), get_permalink( $blog_id ) );
			}
		}
		$items[] = array( get_the_title( $post ), '' );
	} elseif ( is_home() ) {
		$items[] = array( single_post_title( '', false ), '' );
	} elseif ( is_archive() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	} elseif ( is_search() ) {
		$items[] = array( __( 'Search results', 'divi-sales-child' ), '' );
	} else {
		return '';
	}

	$html = '<nav class="gdi-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'divi-sales-child' ) . '"><ol>';
	foreach ( $items as $item ) {
		list( $label, $url ) = $item;
		if ( '' !== $url ) {
			$html .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
		} else {
			$html .= '<li><span aria-current="page">' . esc_html( $label ) . '</span></li>';
		}
	}
	$html .= '</ol></nav>';
	return $html;
}
add_shortcode( 'gdi_breadcrumbs', 'gdi_shell_breadcrumbs_shortcode' );

/**
 * [gdi_subnav] — links to the sibling pages of the current page (or its children, on a parent page).
 * Reuses the .gdi-services-nav styling so existing hand-written in-page navs and this one match.
 */
function gdi_shell_subnav_shortcode() {
	if ( ! is_page() ) {
		return '';
	}
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return '';
	}

	$parent_id = $page->post_parent ? (int) $page->post_parent : (int) $page->ID;
	$pages     = get_pages(
		array(
			'parent'      => $parent_id,
			'sort_column' => 'menu_order,post_title',
			'post_status' => 'publish',
		)
	);
	if ( ! is_array( $pages ) || count( $pages ) < 2 ) {
		return '';
	}

	$html = '<nav class="gdi-services-nav gdi-subnav" aria-label="' . esc_attr__( 'In this section', 'divi-sales-child' ) . '">';
	foreach ( $pages as $item ) {
		$current = ( (int) $item->ID === (int) $page->ID ) ? ' aria-current="page"' : '';
		$html   .= '<a href="' . esc_url( get_permalink( $item ) ) . '"' . $current . '>' . esc_html( get_the_title( $item ) ) . '</a>';
	}
	$html .= '</nav>';
	return $html;
}
add_shortcode( 'gdi_subnav', 'gdi_shell_subnav_shortcode' );

/**
 * [gdi_footer_sitemap] — sitemap columns from the "Footer sitemap" menu location.
 * Top-level items that have children become a column (heading = the item, links = its children);
 * top-level items without children are gathered into one "Explore" column. If no menu is
 * assigned to the location, the primary menu is shown as a single column.
 */
function gdi_shell_sitemap_shortcode() {
	$locations = get_nav_menu_locations();
	$menu_id   = ! empty( $locations['gdi-footer-sitemap'] ) ? (int) $locations['gdi-footer-sitemap'] : 0;
	$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : false;

	$columns = array(); // each: array( 'title' => string, 'links' => array of items ).

	if ( is_array( $items ) && $items ) {
		$children = array();
		$top      = array();
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent ) {
				$children[ (int) $item->menu_item_parent ][] = $item;
			} else {
				$top[] = $item;
			}
		}
		$loose = array();
		foreach ( $top as $item ) {
			if ( ! empty( $children[ (int) $item->ID ] ) ) {
				$columns[] = array(
					'title' => $item->title,
					'links' => $children[ (int) $item->ID ],
				);
			} else {
				$loose[] = $item;
			}
		}
		if ( $loose ) {
			array_unshift(
				$columns,
				array(
					'title' => __( 'Explore', 'divi-sales-child' ),
					'links' => $loose,
				)
			);
		}
	} else {
		$primary_locations = get_nav_menu_locations();
		$primary_id        = ! empty( $primary_locations['primary-menu'] ) ? (int) $primary_locations['primary-menu'] : 0;
		$primary_items     = $primary_id ? wp_get_nav_menu_items( $primary_id ) : false;
		if ( is_array( $primary_items ) && $primary_items ) {
			$columns[] = array(
				'title' => __( 'Explore', 'divi-sales-child' ),
				'links' => $primary_items,
			);
		}
	}

	if ( ! $columns ) {
		return '';
	}

	$current_id = is_page() ? (int) get_queried_object_id() : 0;

	$html = '<nav class="gdi-sitemap" aria-label="' . esc_attr__( 'Site map', 'divi-sales-child' ) . '">';
	foreach ( $columns as $column ) {
		$html .= '<div class="gdi-sitemap-column"><p class="gdi-sitemap-title">' . esc_html( $column['title'] ) . '</p><ul>';
		foreach ( $column['links'] as $link ) {
			$is_current = $current_id && 'post_type' === $link->type && (int) $link->object_id === $current_id;
			$html      .= '<li><a href="' . esc_url( $link->url ) . '"' . ( $is_current ? ' aria-current="page"' : '' ) . '>' . esc_html( $link->title ) . '</a></li>';
		}
		$html .= '</ul></div>';
	}
	$html .= '</nav>';
	return $html;
}
add_shortcode( 'gdi_footer_sitemap', 'gdi_shell_sitemap_shortcode' );
