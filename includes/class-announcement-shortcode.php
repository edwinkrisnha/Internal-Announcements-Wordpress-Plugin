<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Shortcode {

	public function __construct() {
		add_shortcode( 'announcements', array( $this, 'render' ) );

		// Detect shortcode presence after WP has set up $post, then conditionally
		// register the enqueue action — avoids loading assets on every page.
		add_action( 'wp', array( $this, 'maybe_enqueue_assets' ) );
	}

	// -----------------------------------------------------------------------
	// Asset loading
	// -----------------------------------------------------------------------

	public function maybe_enqueue_assets(): void {
		global $post;

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'announcements' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
	}

	public function enqueue_assets(): void {
		wp_enqueue_style(
			'internal-announcements',
			IA_PLUGIN_URL . 'assets/css/announcements.css',
			array(),
			IA_VERSION
		);
	}

	// -----------------------------------------------------------------------
	// Shortcode render
	// -----------------------------------------------------------------------

	/**
	 * [announcements] shortcode.
	 *
	 * Attributes:
	 *   limit    (int)    Max non-pinned posts to show. Default 10.
	 *   category (string) Taxonomy slug to filter by. Default '' (all).
	 *   new_days (int)    Posts published within this many days get a "New" badge. Default 7.
	 */
	public function render( array $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="ia-login-notice">'
				. esc_html__( 'You must be logged in to view announcements.', 'internal-announcements' )
				. '</p>';
		}

		$atts = shortcode_atts(
			array(
				'limit'    => 10,
				'category' => '',
				'new_days' => 7,
			),
			$atts,
			'announcements'
		);

		$limit        = max( 1, (int) $atts['limit'] );
		$category     = sanitize_text_field( $atts['category'] );
		$new_days     = max( 0, (int) $atts['new_days'] );
		$new_after_ts = $new_days > 0 ? strtotime( "-{$new_days} days" ) : false;

		// Optional taxonomy filter.
		$tax_query = array();
		if ( $category ) {
			$tax_query[] = array(
				'taxonomy' => 'announcement_category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}

		$base_args = array(
			'post_type'              => 'announcement',
			'post_status'            => 'publish',
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
		);

		if ( $tax_query ) {
			$base_args['tax_query'] = $tax_query;
		}

		// --- Query 1: pinned (always show all, no limit) ----------------------
		$pinned_query = new WP_Query( array_merge( $base_args, array(
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_is_pinned',
					'value'   => '1',
					'compare' => '=',
				),
			),
			'orderby' => 'date',
			'order'   => 'DESC',
		) ) );

		// --- Query 2: non-pinned (limited, most recent first) -----------------
		// Covers both "meta key absent" and "meta key set to 0".
		$recent_query = new WP_Query( array_merge( $base_args, array(
			'posts_per_page' => $limit,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_is_pinned',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_is_pinned',
					'value'   => '1',
					'compare' => '!=',
				),
			),
			'orderby' => 'date',
			'order'   => 'DESC',
		) ) );

		$posts = array_merge( $pinned_query->posts, $recent_query->posts );

		ob_start();
		include IA_PLUGIN_DIR . 'templates/announcements-feed.php';
		return ob_get_clean();
	}
}
