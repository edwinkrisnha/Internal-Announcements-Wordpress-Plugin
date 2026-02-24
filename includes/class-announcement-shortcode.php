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
	 * All attributes are optional — they default to the values saved in
	 * Announcements → Settings. Passing an attribute explicitly overrides
	 * the saved setting for that page only.
	 *
	 * Attributes:
	 *   mode     (string) 'fixed' or 'days' — overrides the saved display mode.
	 *   limit    (int)    Max non-pinned posts (used when mode = fixed).
	 *   days     (int)    How many days back to show posts (used when mode = days).
	 *   new_days (int)    Posts newer than this many days get a "New" badge (0 = off).
	 *   category (string) Taxonomy slug to filter by. Default '' (all).
	 *   layout   (string) 'list', 'grid-2', or 'grid-3'. Overrides the saved layout.
	 */
	public function render( array $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="ia-login-notice">'
				. esc_html__( 'You must be logged in to view announcements.', 'internal-announcements' )
				. '</p>';
		}

		$settings = Announcement_Settings::get();

		$atts = shortcode_atts(
			array(
				'mode'     => $settings['display_mode'],
				'limit'    => $settings['display_limit'],
				'days'     => $settings['display_days'],
				'new_days' => $settings['new_badge_days'],
				'category' => '',
				'layout'   => $settings['layout'],
			),
			$atts,
			'announcements'
		);

		$mode         = in_array( $atts['mode'], array( 'fixed', 'days' ), true ) ? $atts['mode'] : 'fixed';
		$limit        = max( 1, (int) $atts['limit'] );
		$days         = max( 1, (int) $atts['days'] );
		$new_days     = max( 0, (int) $atts['new_days'] );
		$category     = sanitize_text_field( $atts['category'] );
		$layout       = in_array( $atts['layout'], array( 'list', 'grid-2', 'grid-3' ), true )
			? $atts['layout']
			: 'list';
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

		// Expiry filter: show posts where _expiry_date is absent, blank, or >= today.
		$today        = current_time( 'Y-m-d' );
		$expiry_query = array(
			'relation' => 'OR',
			array(
				'key'     => '_expiry_date',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_expiry_date',
				'value'   => '',
				'compare' => '=',
			),
			array(
				'key'     => '_expiry_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);

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

		// --- Query 1: pinned (all pinned that are not expired) ---------------
		$pinned_query = new WP_Query( array_merge( $base_args, array(
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_is_pinned',
					'value'   => '1',
					'compare' => '=',
				),
				$expiry_query,
			),
			'orderby' => 'date',
			'order'   => 'DESC',
		) ) );

		// --- Query 2: non-pinned (limited by mode, also not expired) ---------
		$non_pinned_meta = array(
			'relation' => 'AND',
			array(
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
			$expiry_query,
		);

		$non_pinned_args = array_merge( $base_args, array(
			'meta_query' => $non_pinned_meta,
			'orderby'    => 'date',
			'order'      => 'DESC',
		) );

		if ( 'days' === $mode ) {
			$non_pinned_args['posts_per_page'] = -1;
			$non_pinned_args['date_query']     = array(
				array(
					'after'     => date( 'Y-m-d', strtotime( "-{$days} days" ) ),
					'inclusive' => true,
				),
			);
		} else {
			$non_pinned_args['posts_per_page'] = $limit;
		}

		$recent_query = new WP_Query( $non_pinned_args );

		$posts = array_merge( $pinned_query->posts, $recent_query->posts );

		ob_start();
		include IA_PLUGIN_DIR . 'templates/announcements-feed.php';
		return ob_get_clean();
	}
}
