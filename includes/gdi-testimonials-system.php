<?php
/**
 * Testimonials custom post type + category taxonomy + REST fields.
 *
 * Ported from fishbucklake.com's fbl-testimonials-system.php
 * (github.com/gaetgodi/Fishbucklake-mu-plugins) — same pattern as
 * gdi-faq-system.php (see that file's header for the `public` => false +
 * REST-still-public rationale, and why this lives in the theme rather
 * than mu-plugins).
 *
 * "Location" from the FBL original is relabelled "Company / Role" here —
 * more useful context for a software client testimonial (e.g. "Stouffville
 * Pickleball Players") than a guest's hometown. Meta keys are this site's
 * own (_gdi_testimonial_*), not FBL's — no legacy data to stay compatible
 * with.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDI_Testimonials_System {

	const POST_TYPE = 'gdi_testimonial';
	const TAXONOMY  = 'gdi_testimonial_category';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );

		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	public function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'labels'              => array(
				'name'               => 'Testimonials',
				'singular_name'      => 'Testimonial',
				'menu_name'          => 'Testimonials',
				'add_new'            => 'Add New Testimonial',
				'add_new_item'       => 'Add New Testimonial',
				'edit_item'          => 'Edit Testimonial',
				'new_item'           => 'New Testimonial',
				'view_item'          => 'View Testimonial',
				'search_items'       => 'Search Testimonials',
				'not_found'          => 'No testimonials found',
				'not_found_in_trash' => 'No testimonials found in trash',
				'all_items'          => 'All Testimonials',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-testimonial',
			'show_in_rest'        => true,
			'rest_base'           => 'testimonials',
			'supports'            => array( 'title', 'editor', 'revisions', 'excerpt' ),
		) );
	}

	public function register_taxonomy() {
		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), array(
			'labels'            => array(
				'name'          => 'Testimonial Categories',
				'singular_name' => 'Testimonial Category',
				'menu_name'     => 'Categories',
				'all_items'     => 'All Categories',
				'edit_item'     => 'Edit Category',
				'add_new_item'  => 'Add New Category',
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'testimonial-categories',
			'rewrite'           => false,
		) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'gdi_testimonial_author_info',
			'Author Information',
			array( $this, 'render_author_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_author_meta_box( $post ) {
		wp_nonce_field( 'gdi_testimonial_author_info', 'gdi_testimonial_author_info_nonce' );

		$author_name = get_post_meta( $post->ID, '_gdi_testimonial_author_name', true );
		$author_role = get_post_meta( $post->ID, '_gdi_testimonial_author_role', true );
		$rating      = get_post_meta( $post->ID, '_gdi_testimonial_rating', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="gdi_testimonial_author_name">Author Name</label></th>
				<td>
					<input type="text" id="gdi_testimonial_author_name" name="gdi_testimonial_author_name"
						value="<?php echo esc_attr( $author_name ); ?>" class="regular-text">
					<p class="description">Example: Shannon, Buck Lake Lodges</p>
				</td>
			</tr>
			<tr>
				<th><label for="gdi_testimonial_author_role">Company / Role</label></th>
				<td>
					<input type="text" id="gdi_testimonial_author_role" name="gdi_testimonial_author_role"
						value="<?php echo esc_attr( $author_role ); ?>" class="regular-text">
					<p class="description">Example: Stouffville Pickleball Players</p>
				</td>
			</tr>
			<tr>
				<th><label for="gdi_testimonial_rating">Rating (optional)</label></th>
				<td>
					<select id="gdi_testimonial_rating" name="gdi_testimonial_rating">
						<option value="">No rating</option>
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, (string) $i ); ?>><?php echo esc_html( $i ); ?> Star<?php echo 1 === $i ? '' : 's'; ?></option>
						<?php endfor; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['gdi_testimonial_author_info_nonce'] )
			|| ! wp_verify_nonce( $_POST['gdi_testimonial_author_info_nonce'], 'gdi_testimonial_author_info' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['gdi_testimonial_author_name'] ) ) {
			update_post_meta( $post_id, '_gdi_testimonial_author_name', sanitize_text_field( wp_unslash( $_POST['gdi_testimonial_author_name'] ) ) );
		}

		if ( isset( $_POST['gdi_testimonial_author_role'] ) ) {
			update_post_meta( $post_id, '_gdi_testimonial_author_role', sanitize_text_field( wp_unslash( $_POST['gdi_testimonial_author_role'] ) ) );
		}

		if ( isset( $_POST['gdi_testimonial_rating'] ) ) {
			update_post_meta( $post_id, '_gdi_testimonial_rating', sanitize_text_field( wp_unslash( $_POST['gdi_testimonial_rating'] ) ) );
		}
	}

	public function custom_columns( $columns ) {
		return array(
			'cb'                          => $columns['cb'],
			'title'                       => 'Title / Excerpt',
			'author_info'                 => 'Author',
			'rating'                      => 'Rating',
			'taxonomy-' . self::TAXONOMY  => 'Category',
			'date'                        => $columns['date'],
		);
	}

	public function custom_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'author_info':
				$name = get_post_meta( $post_id, '_gdi_testimonial_author_name', true );
				$role = get_post_meta( $post_id, '_gdi_testimonial_author_role', true );

				if ( $name ) {
					echo '<strong>' . esc_html( $name ) . '</strong><br>';
				}
				if ( $role ) {
					echo '<small>' . esc_html( $role ) . '</small>';
				}
				break;

			case 'rating':
				$rating = get_post_meta( $post_id, '_gdi_testimonial_rating', true );
				echo $rating ? esc_html( str_repeat( '⭐', intval( $rating ) ) ) : '—';
				break;
		}
	}

	public function register_rest_fields() {
		register_rest_field( self::POST_TYPE, 'author_info', array(
			'get_callback' => array( $this, 'get_author_info' ),
			'schema'       => array(
				'description' => 'Testimonial author information',
				'type'        => 'object',
			),
		) );

		register_rest_field( self::POST_TYPE, 'category_names', array(
			'get_callback' => array( $this, 'get_category_names' ),
			'schema'       => array(
				'description' => 'Testimonial category names',
				'type'        => 'array',
			),
		) );
	}

	public function get_author_info( $object ) {
		return array(
			'name'   => get_post_meta( $object['id'], '_gdi_testimonial_author_name', true ),
			'role'   => get_post_meta( $object['id'], '_gdi_testimonial_author_role', true ),
			'rating' => get_post_meta( $object['id'], '_gdi_testimonial_rating', true ),
		);
	}

	public function get_category_names( $object ) {
		$terms = get_the_terms( $object['id'], self::TAXONOMY );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return wp_list_pluck( $terms, 'name' );
	}
}

new GDI_Testimonials_System();
