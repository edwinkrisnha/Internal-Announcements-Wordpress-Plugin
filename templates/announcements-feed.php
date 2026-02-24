<?php
/**
 * Template: Announcements Feed
 *
 * Variables provided by Announcement_Shortcode::render():
 *
 *   @var WP_Post[] $posts         Merged array — pinned first, then recent.
 *   @var int[]     $read_ids      Post IDs already read by the current user.
 *   @var int       $unread_count  Number of unread posts in this feed result.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ia-announcements" id="ia-announcements">

	<?php if ( $unread_count > 0 ) : ?>
		<p class="ia-unread-summary">
			<?php
			printf(
				esc_html( _n(
					'You have %d unread announcement.',
					'You have %d unread announcements.',
					$unread_count,
					'internal-announcements'
				) ),
				$unread_count
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( empty( $posts ) ) : ?>
		<p class="ia-no-announcements">
			<?php esc_html_e( 'No announcements yet.', 'internal-announcements' ); ?>
		</p>
	<?php else : ?>

		<ul class="ia-announcements-list">
			<?php
			foreach ( $posts as $announcement ) :
				$is_read   = in_array( $announcement->ID, $read_ids, true );
				$is_pinned = (bool) get_post_meta( $announcement->ID, '_is_pinned', true );
				$terms     = get_the_terms( $announcement->ID, 'announcement_category' );

				$item_classes = array_filter( array(
					'ia-announcement',
					$is_read   ? 'ia-read'   : 'ia-unread',
					$is_pinned ? 'ia-pinned' : '',
				) );
			?>
			<li
				class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
				data-post-id="<?php echo esc_attr( $announcement->ID ); ?>"
			>
				<!-- Header row: badges, category, date -->
				<div class="ia-announcement-header">

					<?php if ( $is_pinned ) : ?>
						<span class="ia-pin-badge">
							<?php esc_html_e( 'Pinned', 'internal-announcements' ); ?>
						</span>
					<?php endif; ?>

					<?php if ( ! $is_read ) : ?>
						<span
							class="ia-unread-dot"
							title="<?php esc_attr_e( 'Unread', 'internal-announcements' ); ?>"
							aria-label="<?php esc_attr_e( 'Unread', 'internal-announcements' ); ?>"
						></span>
					<?php endif; ?>

					<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
						<span class="ia-category">
							<?php echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ); ?>
						</span>
					<?php endif; ?>

					<time
						class="ia-date"
						datetime="<?php echo esc_attr( get_the_date( 'c', $announcement ) ); ?>"
					>
						<?php echo esc_html( get_the_date( get_option( 'date_format' ), $announcement ) ); ?>
					</time>

				</div><!-- .ia-announcement-header -->

				<!-- Title -->
				<h3 class="ia-announcement-title">
					<?php echo esc_html( get_the_title( $announcement ) ); ?>
				</h3>

				<!-- Body — rendered through the_content filters (shortcodes, embeds, etc.) -->
				<?php
				// Set up post data so content filters (e.g. Gutenberg blocks) work correctly.
				global $post;
				$post = $announcement; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				$content = get_the_content();
				$content = apply_filters( 'the_content', $content );
				echo wp_kses_post( $content );
				wp_reset_postdata();
				?>

				<!-- Footer: mark-as-read action or read confirmation -->
				<?php if ( ! $is_read ) : ?>
					<button
						class="ia-mark-read-btn"
						data-post-id="<?php echo esc_attr( $announcement->ID ); ?>"
						type="button"
					>
						<?php esc_html_e( 'Mark as read', 'internal-announcements' ); ?>
					</button>
				<?php else : ?>
					<span class="ia-read-label">
						<?php esc_html_e( 'Read', 'internal-announcements' ); ?>
					</span>
				<?php endif; ?>

			</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>

</div><!-- .ia-announcements -->
