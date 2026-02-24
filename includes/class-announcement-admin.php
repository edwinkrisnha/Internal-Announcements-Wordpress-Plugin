<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Admin {

	public function __construct() {
		add_action( 'add_meta_boxes',    array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_announcement', array( $this, 'save_pin_meta' ), 10, 2 );

		// Admin list table.
		add_filter( 'manage_announcement_posts_columns',        array( $this, 'add_columns' ) );
		add_action( 'manage_announcement_posts_custom_column',  array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-announcement_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts',                             array( $this, 'handle_column_orderby' ) );
	}

	// -----------------------------------------------------------------------
	// Meta box — "Announcement Options" (side, high)
	// -----------------------------------------------------------------------

	public function add_meta_boxes(): void {
		add_meta_box(
			'ia_announcement_options',
			__( 'Announcement Options', 'internal-announcements' ),
			array( $this, 'render_meta_box' ),
			'announcement',
			'side',
			'high'
		);
	}

	public function render_meta_box( WP_Post $post ): void {
		$is_pinned = (bool) get_post_meta( $post->ID, '_is_pinned', true );
		wp_nonce_field( 'ia_pin_meta_box', 'ia_pin_nonce' );
		?>
		<p>
			<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
				<input
					type="checkbox"
					name="ia_is_pinned"
					value="1"
					<?php checked( $is_pinned ); ?>
				/>
				<?php esc_html_e( 'Pin to top of feed', 'internal-announcements' ); ?>
			</label>
		</p>
		<p style="color:#757575;font-size:12px;margin-top:4px;">
			<?php esc_html_e( 'Pinned announcements always appear above all others, regardless of date.', 'internal-announcements' ); ?>
		</p>
		<?php
	}

	public function save_pin_meta( int $post_id, WP_Post $post ): void {
		// Skip autosaves and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Nonce verification.
		if (
			! isset( $_POST['ia_pin_nonce'] )
			|| ! wp_verify_nonce( $_POST['ia_pin_nonce'], 'ia_pin_meta_box' )
		) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Unchecked checkboxes are absent from $_POST; default to 0.
		$is_pinned = isset( $_POST['ia_is_pinned'] ) ? 1 : 0;
		update_post_meta( $post_id, '_is_pinned', $is_pinned );
	}

	// -----------------------------------------------------------------------
	// Admin list table columns
	// -----------------------------------------------------------------------

	public function add_columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			// Insert "Pinned" column right after "Title".
			if ( 'title' === $key ) {
				$new['ia_pinned'] = __( 'Pinned', 'internal-announcements' );
			}
		}

		return $new;
	}

	public function render_column( string $column, int $post_id ): void {
		if ( 'ia_pinned' !== $column ) {
			return;
		}

		$is_pinned = (bool) get_post_meta( $post_id, '_is_pinned', true );

		echo $is_pinned
			? '<span aria-label="' . esc_attr__( 'Pinned', 'internal-announcements' ) . '">&#128204;</span>'
			: '<span style="color:#ccc;">&#8212;</span>';
	}

	public function sortable_columns( array $columns ): array {
		$columns['ia_pinned'] = 'ia_pinned';
		return $columns;
	}

	/**
	 * Translate the "ia_pinned" orderby slug into a real meta_key sort
	 * when the admin list table requests it.
	 */
	public function handle_column_orderby( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'announcement' !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( 'ia_pinned' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_is_pinned' );
			$query->set( 'orderby',  'meta_value_num' );
		}
	}
}
