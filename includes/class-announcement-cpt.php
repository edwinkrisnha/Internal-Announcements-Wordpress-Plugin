<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_CPT {

	/**
	 * Register the CPT and taxonomy.
	 * Hooked to `init` and called directly during plugin activation.
	 */
	public static function register(): void {
		self::register_post_type();
		self::register_taxonomy();
	}

	// -----------------------------------------------------------------------
	// Post type
	// -----------------------------------------------------------------------

	private static function register_post_type(): void {
		register_post_type( 'announcement', array(
			'labels'             => array(
				'name'               => __( 'Announcements', 'internal-announcements' ),
				'singular_name'      => __( 'Announcement', 'internal-announcements' ),
				'add_new'            => __( 'Add New', 'internal-announcements' ),
				'add_new_item'       => __( 'Add New Announcement', 'internal-announcements' ),
				'edit_item'          => __( 'Edit Announcement', 'internal-announcements' ),
				'new_item'           => __( 'New Announcement', 'internal-announcements' ),
				'view_item'          => __( 'View Announcement', 'internal-announcements' ),
				'search_items'       => __( 'Search Announcements', 'internal-announcements' ),
				'not_found'          => __( 'No announcements found.', 'internal-announcements' ),
				'not_found_in_trash' => __( 'No announcements found in Trash.', 'internal-announcements' ),
				'menu_name'          => __( 'Announcements', 'internal-announcements' ),
			),
			// Not publicly accessible — content is rendered via shortcode only.
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,   // enables Gutenberg editor in admin.
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => true,
			'menu_icon'          => 'dashicons-megaphone',
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		) );
	}

	// -----------------------------------------------------------------------
	// Taxonomy
	// -----------------------------------------------------------------------

	private static function register_taxonomy(): void {
		register_taxonomy( 'announcement_category', 'announcement', array(
			'labels'            => array(
				'name'          => __( 'Announcement Categories', 'internal-announcements' ),
				'singular_name' => __( 'Announcement Category', 'internal-announcements' ),
				'search_items'  => __( 'Search Categories', 'internal-announcements' ),
				'all_items'     => __( 'All Categories', 'internal-announcements' ),
				'edit_item'     => __( 'Edit Category', 'internal-announcements' ),
				'update_item'   => __( 'Update Category', 'internal-announcements' ),
				'add_new_item'  => __( 'Add New Category', 'internal-announcements' ),
				'new_item_name' => __( 'New Category Name', 'internal-announcements' ),
				'menu_name'     => __( 'Categories', 'internal-announcements' ),
			),
			'hierarchical'      => true,   // category-style (not tag-style).
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => false,
		) );
	}

	// -----------------------------------------------------------------------
	// Default terms
	// -----------------------------------------------------------------------

	/**
	 * Seed the three default categories on activation.
	 * Safe to call multiple times — `term_exists()` guards against duplicates.
	 */
	public static function seed_default_terms(): void {
		$defaults = array( 'General', 'HR', 'IT' );

		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, 'announcement_category' ) ) {
				wp_insert_term( $term, 'announcement_category' );
			}
		}
	}
}
