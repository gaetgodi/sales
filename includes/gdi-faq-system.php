<?php
/**
 * FAQ custom post type + category taxonomy + REST fields.
 *
 * Ported from fishbucklake.com's fbl-faq-system.php (github.com/gaetgodi/
 * Fishbucklake-mu-plugins) — same portable CPT + REST API + JS front-end
 * pattern (see gdi-faq.js), renamed to this site's own gdi_ prefix and
 * restyled via 03-faq-testimonials.css instead of FBL's dark/gold skin.
 * `public` => false + `show_in_rest` => true still serves the REST
 * collection to anonymous visitors on FBL's live site (confirmed directly
 * against https://fishbucklake.com/wp-json/wp/v2/faqs before porting this
 * over) — WordPress's read-permission check for a non-public post type's
 * REST collection only restricts single-post access, not the list route.
 *
 * Lives in the theme (not wp-content/mu-plugins/, unlike FBL's copy)
 * because this site's mu-plugins directory isn't its own git repo — this
 * theme is the only version-controlled surface for godindev.com.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDI_FAQ_System {

	const POST_TYPE = 'gdi_faq';
	const TAXONOMY  = 'gdi_faq_category';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );

		add_action( 'restrict_manage_posts', array( $this, 'add_category_filter' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'                  => 'FAQs',
			'singular_name'         => 'FAQ',
			'menu_name'             => 'FAQs',
			'add_new'               => 'Add New FAQ',
			'add_new_item'          => 'Add New FAQ',
			'edit_item'             => 'Edit FAQ',
			'new_item'              => 'New FAQ',
			'view_item'             => 'View FAQ',
			'view_items'            => 'View FAQs',
			'search_items'          => 'Search FAQs',
			'not_found'             => 'No FAQs found',
			'not_found_in_trash'    => 'No FAQs found in trash',
			'all_items'             => 'All FAQs',
			'insert_into_item'      => 'Insert into FAQ',
			'uploaded_to_this_item' => 'Uploaded to this FAQ',
		);

		register_post_type( self::POST_TYPE, array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'capabilities'        => array(
				'edit_post'          => 'edit_post',
				'read_post'          => 'read_post',
				'delete_post'        => 'delete_post',
				'edit_posts'         => 'edit_posts',
				'edit_others_posts'  => 'edit_others_posts',
				'publish_posts'      => 'publish_posts',
				'read_private_posts' => 'read_private_posts',
				'delete_posts'       => 'delete_posts',
			),
			'map_meta_cap'        => true,
			'query_var'           => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-editor-help',
			'show_in_rest'        => true,
			'rest_base'           => 'faqs',
			'supports'            => array( 'title', 'editor', 'revisions' ),
		) );
	}

	public function register_taxonomy() {
		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), array(
			'labels'            => array(
				'name'                  => 'FAQ Categories',
				'singular_name'         => 'FAQ Category',
				'menu_name'             => 'Categories',
				'all_items'             => 'All Categories',
				'edit_item'             => 'Edit Category',
				'view_item'             => 'View Category',
				'update_item'           => 'Update Category',
				'add_new_item'          => 'Add New Category',
				'new_item_name'         => 'New Category Name',
				'search_items'          => 'Search Categories',
				'not_found'             => 'No categories found',
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'faq-categories',
			'rewrite'           => false,
		) );
	}

	public function custom_columns( $columns ) {
		return array(
			'cb'                          => $columns['cb'],
			'title'                       => 'Question',
			'taxonomy-' . self::TAXONOMY  => 'Category',
			'answer_preview'              => 'Answer',
			'date'                        => $columns['date'],
		);
	}

	public function custom_column_content( $column, $post_id ) {
		if ( 'answer_preview' === $column ) {
			$content = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
			echo esc_html( substr( $content, 0, 100 ) ) . '...';
		}
	}

	public function sortable_columns( $columns ) {
		$columns['title'] = 'title';
		return $columns;
	}

	public function add_category_filter() {
		global $typenow;

		if ( self::POST_TYPE !== $typenow ) {
			return;
		}

		wp_dropdown_categories( array(
			'show_option_all' => 'All Categories',
			'taxonomy'        => self::TAXONOMY,
			'name'            => self::TAXONOMY,
			'orderby'         => 'name',
			'selected'        => isset( $_GET[ self::TAXONOMY ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::TAXONOMY ] ) ) : '',
			'show_count'      => true,
			'hide_empty'      => false,
			'value_field'     => 'slug',
		) );
	}

	public function register_rest_fields() {
		register_rest_field( self::POST_TYPE, 'category_names', array(
			'get_callback' => array( $this, 'get_category_names' ),
			'schema'       => array(
				'description' => 'FAQ category names',
				'type'        => 'array',
			),
		) );

		register_rest_field( self::POST_TYPE, 'answer', array(
			'get_callback' => array( $this, 'get_answer_html' ),
			'schema'       => array(
				'description' => 'FAQ answer with HTML',
				'type'        => 'string',
			),
		) );
	}

	public function get_category_names( $object ) {
		$terms = get_the_terms( $object['id'], self::TAXONOMY );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return wp_list_pluck( $terms, 'name' );
	}

	public function get_answer_html( $object ) {
		return apply_filters( 'the_content', get_post_field( 'post_content', $object['id'] ) );
	}
}

new GDI_FAQ_System();
