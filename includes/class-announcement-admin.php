<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Admin {

	public function __construct() {
		add_action( 'add_meta_boxes',         array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_announcement', array( $this, 'save_meta' ), 10, 2 );

		// Admin list table.
		add_filter( 'manage_announcement_posts_columns',         array( $this, 'add_columns' ) );
		add_action( 'manage_announcement_posts_custom_column',   array( $this, 'render_column' ), 10, 2 );
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
		$is_pinned   = (bool) get_post_meta( $post->ID, '_is_pinned', true );
		$expiry_date = get_post_meta( $post->ID, '_expiry_date', true );

		wp_nonce_field( 'ia_announcement_meta_box', 'ia_meta_nonce' );
		?>

		<!-- Pin -->
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
		<p style="color:#757575;font-size:12px;margin-top:2px;margin-bottom:16px;">
			<?php esc_html_e( 'Pinned announcements always appear above all others, regardless of date.', 'internal-announcements' ); ?>
		</p>

		<!-- Expiry date -->
		<p>
			<label for="ia_expiry_date" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php esc_html_e( 'Expiry date', 'internal-announcements' ); ?>
			</label>
			<input
				type="date"
				id="ia_expiry_date"
				name="ia_expiry_date"
				value="<?php echo esc_attr( $expiry_date ); ?>"
				style="width:100%;"
			/>
		</p>
		<p style="color:#757575;font-size:12px;margin-top:4px;">
			<?php esc_html_e( 'Announcement is automatically hidden from the feed after this date. Leave blank for no expiry.', 'internal-announcements' ); ?>
		</p>
		<?php
	}

	public function save_meta( int $post_id, WP_Post $post ): void {
		// Skip autosaves and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Nonce verification.
		if (
			! isset( $_POST['ia_meta_nonce'] )
			|| ! wp_verify_nonce( $_POST['ia_meta_nonce'], 'ia_announcement_meta_box' )
		) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// --- Pin ------------------------------------------------------------
		$is_pinned = isset( $_POST['ia_is_pinned'] ) ? 1 : 0;
		update_post_meta( $post_id, '_is_pinned', $is_pinned );

		// --- Expiry date ----------------------------------------------------
		// Store as Y-m-d or empty string. Validate format before saving.
		$expiry_raw = isset( $_POST['ia_expiry_date'] ) ? sanitize_text_field( $_POST['ia_expiry_date'] ) : '';

		if ( '' === $expiry_raw ) {
			update_post_meta( $post_id, '_expiry_date', '' );
		} else {
			// Ensure the value is a valid date in Y-m-d format.
			$parsed = DateTimeImmutable::createFromFormat( 'Y-m-d', $expiry_raw );
			if ( $parsed && $parsed->format( 'Y-m-d' ) === $expiry_raw ) {
				update_post_meta( $post_id, '_expiry_date', $expiry_raw );
			}
			// If invalid, silently keep the existing value (no meta update).
		}
	}

	// -----------------------------------------------------------------------
	// Admin list table columns
	// -----------------------------------------------------------------------

	public function add_columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['ia_pinned']  = __( 'Pinned', 'internal-announcements' );
				$new['ia_expires'] = __( 'Expires', 'internal-announcements' );
			}
		}

		return $new;
	}

	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'ia_pinned':
				$is_pinned = (bool) get_post_meta( $post_id, '_is_pinned', true );
				echo $is_pinned
					? '<span aria-label="' . esc_attr__( 'Pinned', 'internal-announcements' ) . '">&#128204;</span>'
					: '<span style="color:#ccc;">&#8212;</span>';
				break;

			case 'ia_expires':
				$expiry = get_post_meta( $post_id, '_expiry_date', true );

				if ( ! $expiry ) {
					echo '<span style="color:#ccc;">&#8212;</span>';
					break;
				}

				$today    = current_time( 'Y-m-d' );
				$expired  = $expiry < $today;
				$label    = date_i18n( get_option( 'date_format' ), strtotime( $expiry ) );
				$color    = $expired ? '#b32d2e' : '#1d2327';
				$suffix   = $expired ? ' &mdash; ' . esc_html__( 'Expired', 'internal-announcements' ) : '';

				printf(
					'<span style="color:%s;">%s%s</span>',
					esc_attr( $color ),
					esc_html( $label ),
					$suffix // already escaped above.
				);
				break;
		}
	}

	public function sortable_columns( array $columns ): array {
		$columns['ia_pinned']  = 'ia_pinned';
		$columns['ia_expires'] = 'ia_expires';
		return $columns;
	}

	/**
	 * Translate custom orderby slugs to real meta_key sorts for the admin list.
	 */
	public function handle_column_orderby( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'announcement' !== $query->get( 'post_type' ) ) {
			return;
		}

		switch ( $query->get( 'orderby' ) ) {
			case 'ia_pinned':
				$query->set( 'meta_key', '_is_pinned' );
				$query->set( 'orderby',  'meta_value_num' );
				break;

			case 'ia_expires':
				$query->set( 'meta_key', '_expiry_date' );
				$query->set( 'orderby',  'meta_value' );
				break;
		}
	}
}
