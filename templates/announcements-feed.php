<?php
/**
 * Template: Announcements Feed
 *
 * Variables provided by Announcement_Shortcode::render():
 *
 *   @var WP_Post[] $posts         Merged array — pinned first, then recent.
 *   @var int       $new_days      Posts newer than this many days get a "New" badge.
 *   @var int|false $new_after_ts  Unix timestamp threshold, or false if new_days = 0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ia-announcements" id="ia-announcements">

	<?php if ( empty( $posts ) ) : ?>
		<p class="ia-no-announcements">
			<?php esc_html_e( 'No announcements yet.', 'internal-announcements' ); ?>
		</p>
	<?php else : ?>

		<ul class="ia-announcements-list">
			<?php
			foreach ( $posts as $announcement ) :
				$is_pinned = (bool) get_post_meta( $announcement->ID, '_is_pinned', true );
				$is_new    = $new_after_ts && ( strtotime( $announcement->post_date ) >= $new_after_ts );
				$terms     = get_the_terms( $announcement->ID, 'announcement_category' );

				$item_classes = array_filter( array(
					'ia-announcement',
					$is_pinned ? 'ia-pinned' : '',
					$is_new    ? 'ia-is-new' : '',
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

					<?php if ( $is_new ) : ?>
						<span class="ia-new-badge">
							<?php esc_html_e( 'New', 'internal-announcements' ); ?>
						</span>
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
				global $post;
				$post = $announcement; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				$content = get_the_content();
				$content = apply_filters( 'the_content', $content );
				echo wp_kses_post( $content );
				wp_reset_postdata();
				?>

			</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>

</div><!-- .ia-announcements -->
