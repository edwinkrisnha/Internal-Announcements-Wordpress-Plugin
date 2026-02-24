<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Ajax {

	public function __construct() {
		// Only for logged-in users — no nopriv handler intentionally.
		add_action( 'wp_ajax_ia_mark_read', array( $this, 'handle_mark_read' ) );
	}

	// -----------------------------------------------------------------------
	// Handlers
	// -----------------------------------------------------------------------

	public function handle_mark_read(): void {
		// 1. CSRF check — dies with 403 on failure.
		check_ajax_referer( 'ia_mark_read', 'nonce' );

		// 2. Auth check — redundant given wp_ajax_* only fires for logged-in
		//    users, but explicit is better than implicit.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
		}

		// 3. Input validation.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Invalid post ID.' ), 400 );
		}

		// 4. Verify the post exists, is an announcement, and is published.
		$post = get_post( $post_id );

		if (
			! $post
			|| 'announcement' !== $post->post_type
			|| 'publish'      !== $post->post_status
		) {
			wp_send_json_error( array( 'message' => 'Announcement not found.' ), 404 );
		}

		// 5. Mark as read.
		Announcement_Read_Tracker::mark_read( get_current_user_id(), $post_id );

		wp_send_json_success( array( 'post_id' => $post_id ) );
	}
}
