<?php
/**
 * Template: Announcements Feed
 *
 * Variables provided by Announcement_Shortcode::render():
 *
 *   @var WP_Post[] $posts         Merged array — pinned first, then recent.
 *   @var int       $new_days      Posts newer than this many days get a "New" badge.
 *   @var int|false $new_after_ts  Unix timestamp threshold, or false if new_days = 0.
 *   @var string    $layout        'list', 'grid-2', or 'grid-3'.
 *   @var bool      $show_author   Whether to display author name + avatar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Pre-pass: collect unique terms present in this feed for the filter tabs.
// We gather them here (before the render loop) so:
//   - The filter UI can be rendered before the list.
//   - We don't call get_the_terms() twice per post.
// ---------------------------------------------------------------------------
$feed_terms    = array(); // [ term_id => WP_Term ] — unique terms in this feed.
$post_term_map = array(); // [ post_id => WP_Term[] ] — reused in the render loop.

foreach ( $posts as $p ) {
	$terms = get_the_terms( $p->ID, 'announcement_category' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$post_term_map[ $p->ID ] = $terms;
		foreach ( $terms as $term ) {
			$feed_terms[ $term->term_id ] = $term;
		}
	} else {
		$post_term_map[ $p->ID ] = array();
	}
}

$show_filter = count( $feed_terms ) > 1;
?>
<div class="ia-announcements ia-layout--<?php echo esc_attr( $layout ); ?>" id="ia-announcements">

	<?php if ( $show_filter ) : ?>
		<?php
		// ---------------------------------------------------------------------------
		// CSS-only category filter.
		//
		// Pattern: hidden radio inputs + visible <label> tabs.
		// The general sibling combinator (~) matches .ia-announcements-list
		// (and .ia-filter-tabs) because they follow the radios in the DOM.
		// Cards carry a data-categories attribute (space-separated slugs) so
		// CSS attribute selectors can show/hide them without JS.
		// ---------------------------------------------------------------------------
		?>

		<!-- Hidden radio inputs — must precede the tabs and the list in the DOM -->
		<input
			type="radio"
			name="ia-cat-filter"
			id="ia-cat-filter-all"
			class="ia-filter-radio"
			value="all"
			checked
			hidden
		/>
		<?php foreach ( $feed_terms as $term ) : ?>
			<input
				type="radio"
				name="ia-cat-filter"
				id="ia-cat-filter-<?php echo esc_attr( $term->term_id ); ?>"
				class="ia-filter-radio"
				value="<?php echo esc_attr( $term->slug ); ?>"
				hidden
			/>
		<?php endforeach; ?>

		<!-- Inline CSS: one rule per category (generated from PHP, avoids extra HTTP request) -->
		<style>
		<?php foreach ( $feed_terms as $term ) :
			$radio_id = 'ia-cat-filter-' . $term->term_id;
			$slug     = $term->slug;
		?>
		/* Hide non-matching cards when this category is selected */
		#<?php echo esc_attr( $radio_id ); ?>:checked ~ .ia-announcements-list
			.ia-announcement:not([data-categories~="<?php echo esc_attr( $slug ); ?>"]) {
			display: none;
		}
		/* Highlight the active tab */
		#<?php echo esc_attr( $radio_id ); ?>:checked ~ .ia-filter-tabs
			label[for="<?php echo esc_attr( $radio_id ); ?>"] {
			background: #2271b1;
			color: #fff;
			border-color: #2271b1;
		}
		<?php endforeach; ?>
		</style>

		<!-- Visible tab labels (siblings to the radios, so ~ works) -->
		<div class="ia-filter-tabs" role="tablist">
			<label for="ia-cat-filter-all" class="ia-filter-tab" role="tab">
				<?php esc_html_e( 'All', 'internal-announcements' ); ?>
			</label>
			<?php foreach ( $feed_terms as $term ) : ?>
				<label
					for="ia-cat-filter-<?php echo esc_attr( $term->term_id ); ?>"
					class="ia-filter-tab"
					style="<?php echo esc_attr( Announcement_Settings::get_category_inline_style( $term->term_id ) ); ?>"
					role="tab"
				>
					<?php echo esc_html( $term->name ); ?>
				</label>
			<?php endforeach; ?>
		</div>

	<?php endif; // show_filter ?>

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
				$terms     = $post_term_map[ $announcement->ID ];

				// Build space-separated slug list for CSS data-attribute filtering.
				$category_slugs = implode( ' ', wp_list_pluck( $terms, 'slug' ) );

				$item_classes = array_filter( array(
					'ia-announcement',
					$is_pinned ? 'ia-pinned' : '',
					$is_new    ? 'ia-is-new' : '',
				) );
			?>
			<li
				class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
				data-post-id="<?php echo esc_attr( $announcement->ID ); ?>"
				<?php if ( $show_filter && $category_slugs ) : ?>
					data-categories="<?php echo esc_attr( $category_slugs ); ?>"
				<?php endif; ?>
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

					<?php foreach ( $terms as $term ) : ?>
						<span
							class="ia-category"
							style="<?php echo esc_attr( Announcement_Settings::get_category_inline_style( $term->term_id ) ); ?>"
						>
							<?php echo esc_html( $term->name ); ?>
						</span>
					<?php endforeach; ?>

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

				<!-- Author attribution (optional, controlled by Settings) -->
				<?php if ( $show_author ) : ?>
					<div class="ia-author">
						<?php echo get_avatar( $announcement->post_author, 24, '', '', array( 'class' => 'ia-author-avatar' ) ); ?>
						<span class="ia-author-name">
							<?php echo esc_html( get_the_author_meta( 'display_name', $announcement->post_author ) ); ?>
						</span>
					</div>
				<?php endif; ?>

			</li>
			<?php endforeach; ?>
		</ul>

	<?php endif; ?>

</div><!-- .ia-announcements -->
