<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Settings {

	const OPTION_KEY = 'ia_settings';

	/**
	 * Color palette — background / text pairs.
	 * Assigned to categories by (term_id % palette_size) so each category
	 * always gets the same color without any user configuration.
	 */
	private const PALETTE = array(
		array( 'bg' => '#dbeafe', 'color' => '#1e40af' ), // Blue
		array( 'bg' => '#d1fae5', 'color' => '#065f46' ), // Emerald
		array( 'bg' => '#fef3c7', 'color' => '#92400e' ), // Amber
		array( 'bg' => '#ffe4e6', 'color' => '#9f1239' ), // Rose
		array( 'bg' => '#ede9fe', 'color' => '#5b21b6' ), // Violet
		array( 'bg' => '#ffedd5', 'color' => '#9a3412' ), // Orange
		array( 'bg' => '#ccfbf1', 'color' => '#115e59' ), // Teal
		array( 'bg' => '#fce7f3', 'color' => '#831843' ), // Pink
	);

	private const DEFAULTS = array(
		'display_mode'   => 'fixed', // 'fixed' | 'days'
		'display_limit'  => 10,      // max posts when mode = fixed
		'display_days'   => 30,      // date range (days back) when mode = days
		'new_badge_days' => 7,       // posts newer than this get a "New" badge (0 = off)
		'layout'         => 'list',  // 'list' | 'grid-2' | 'grid-3'
		'show_author'    => false,   // show post author name + avatar on each card
	);

	/** Per-request option cache — avoids repeated deserialization of the same option. */
	private static array $cache = array();

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------

	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'register_menu' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	// -----------------------------------------------------------------------
	// Menu
	// -----------------------------------------------------------------------

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=announcement',
			__( 'Announcement Settings', 'internal-announcements' ),
			__( 'Settings', 'internal-announcements' ),
			'manage_options',
			'ia-settings',
			array( $this, 'render_page' )
		);
	}

	// -----------------------------------------------------------------------
	// Settings API
	// -----------------------------------------------------------------------

	public function register_settings(): void {
		register_setting(
			'ia_settings_group',
			self::OPTION_KEY,
			array( 'sanitize_callback' => array( $this, 'sanitize' ) )
		);

		// --- Section: Feed Display ------------------------------------------
		add_settings_section(
			'ia_section_display',
			__( 'Feed Display', 'internal-announcements' ),
			'__return_false',
			'ia-settings'
		);

		add_settings_field(
			'ia_display_mode',
			__( 'Display mode', 'internal-announcements' ),
			array( $this, 'field_display_mode' ),
			'ia-settings',
			'ia_section_display'
		);

		add_settings_field(
			'ia_display_limit',
			__( 'Number to show', 'internal-announcements' ),
			array( $this, 'field_display_limit' ),
			'ia-settings',
			'ia_section_display'
		);

		add_settings_field(
			'ia_display_days',
			__( 'Days to look back', 'internal-announcements' ),
			array( $this, 'field_display_days' ),
			'ia-settings',
			'ia_section_display'
		);

		add_settings_field(
			'ia_layout',
			__( 'Layout', 'internal-announcements' ),
			array( $this, 'field_layout' ),
			'ia-settings',
			'ia_section_display'
		);

		add_settings_field(
			'ia_show_author',
			__( 'Show author', 'internal-announcements' ),
			array( $this, 'field_show_author' ),
			'ia-settings',
			'ia_section_display'
		);

		// --- Section: Badges ------------------------------------------------
		add_settings_section(
			'ia_section_badges',
			__( 'Badges', 'internal-announcements' ),
			'__return_false',
			'ia-settings'
		);

		add_settings_field(
			'ia_new_badge_days',
			__( '"New" badge duration', 'internal-announcements' ),
			array( $this, 'field_new_badge_days' ),
			'ia-settings',
			'ia_section_badges'
		);

		// --- Section: Categories --------------------------------------------
		add_settings_section(
			'ia_section_categories',
			__( 'Category Colors', 'internal-announcements' ),
			array( $this, 'section_categories_description' ),
			'ia-settings'
		);
	}

	// -----------------------------------------------------------------------
	// Field renderers
	// -----------------------------------------------------------------------

	public function field_display_mode(): void {
		$settings = self::get();
		?>
		<fieldset>
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]"
					value="fixed"
					<?php checked( $settings['display_mode'], 'fixed' ); ?>
				/>
				<?php esc_html_e( 'Fixed number of announcements', 'internal-announcements' ); ?>
			</label>
			<br /><br />
			<label>
				<input
					type="radio"
					name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]"
					value="days"
					<?php checked( $settings['display_mode'], 'days' ); ?>
				/>
				<?php esc_html_e( 'All announcements from the last X days', 'internal-announcements' ); ?>
			</label>
		</fieldset>
		<?php
	}

	public function field_display_limit(): void {
		$settings = self::get();
		?>
		<div id="ia-field-display-limit">
			<input
				type="number"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_limit]"
				value="<?php echo esc_attr( $settings['display_limit'] ); ?>"
				min="1"
				max="100"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Maximum number of (non-pinned) announcements to show. Pinned announcements are always shown in full.', 'internal-announcements' ); ?>
			</p>
		</div>
		<?php
	}

	public function field_display_days(): void {
		$settings = self::get();
		?>
		<div id="ia-field-display-days">
			<input
				type="number"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_days]"
				value="<?php echo esc_attr( $settings['display_days'] ); ?>"
				min="1"
				max="365"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Show all announcements published within this many days. Pinned announcements are always shown in full.', 'internal-announcements' ); ?>
			</p>
		</div>
		<?php
	}

	public function field_layout(): void {
		$settings = self::get();
		$options  = array(
			'list'   => __( 'List (single column)', 'internal-announcements' ),
			'grid-2' => __( 'Grid — 2 columns', 'internal-announcements' ),
			'grid-3' => __( 'Grid — 3 columns', 'internal-announcements' ),
		);
		?>
		<fieldset>
			<?php foreach ( $options as $value => $label ) : ?>
				<label style="display:block;margin-bottom:8px;">
					<input
						type="radio"
						name="<?php echo esc_attr( self::OPTION_KEY ); ?>[layout]"
						value="<?php echo esc_attr( $value ); ?>"
						<?php checked( $settings['layout'], $value ); ?>
					/>
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="description">
			<?php esc_html_e( 'Grid layouts collapse to a single column on small screens. The layout shortcode attribute overrides this setting per placement.', 'internal-announcements' ); ?>
		</p>
		<?php
	}

	public function field_show_author(): void {
		$settings = self::get();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[show_author]"
				value="1"
				<?php checked( $settings['show_author'] ); ?>
			/>
			<?php esc_html_e( 'Display the author\'s name and avatar on each announcement card', 'internal-announcements' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Uses the WordPress display name and Gravatar. If your intranet has no external internet access, disable Gravatar in Settings → Discussion instead.', 'internal-announcements' ); ?>
		</p>
		<?php
	}

	public function field_new_badge_days(): void {
		$settings = self::get();
		?>
		<input
			type="number"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[new_badge_days]"
			value="<?php echo esc_attr( $settings['new_badge_days'] ); ?>"
			min="0"
			max="365"
			class="small-text"
		/>
		<p class="description">
			<?php esc_html_e( 'Announcements published within this many days display a "New" badge. Set to 0 to disable the badge entirely.', 'internal-announcements' ); ?>
		</p>
		<?php
	}

	public function section_categories_description(): void {
		$terms = get_terms( array(
			'taxonomy'   => 'announcement_category',
			'hide_empty' => false,
		) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<p>' . esc_html__( 'No categories found.', 'internal-announcements' ) . '</p>';
			return;
		}

		echo '<p>' . esc_html__( 'Colors are automatically assigned from the palette below. The assignment is stable — each category always keeps the same color.', 'internal-announcements' ) . '</p>';
		echo '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">';

		foreach ( $terms as $term ) {
			$style = self::get_category_inline_style( $term->term_id );
			echo '<span style="' . esc_attr( $style ) . 'padding:4px 12px;border-radius:3px;font-size:13px;font-weight:500;">'
				. esc_html( $term->name )
				. '</span>';
		}

		echo '</div>';
	}

	// -----------------------------------------------------------------------
	// Sanitize
	// -----------------------------------------------------------------------

	public function sanitize( mixed $input ): array {
		// Clear the request cache so any get() calls after this save see fresh DB data.
		self::$cache = array();

		$clean = self::get(); // reads current saved values as sanitize fallback.

		if ( isset( $input['display_mode'] ) && in_array( $input['display_mode'], array( 'fixed', 'days' ), true ) ) {
			$clean['display_mode'] = $input['display_mode'];
		}

		if ( isset( $input['display_limit'] ) ) {
			$clean['display_limit'] = max( 1, min( 100, (int) $input['display_limit'] ) );
		}

		if ( isset( $input['display_days'] ) ) {
			$clean['display_days'] = max( 1, min( 365, (int) $input['display_days'] ) );
		}

		if ( isset( $input['new_badge_days'] ) ) {
			$clean['new_badge_days'] = max( 0, min( 365, (int) $input['new_badge_days'] ) );
		}

		if ( isset( $input['layout'] ) && in_array( $input['layout'], array( 'list', 'grid-2', 'grid-3' ), true ) ) {
			$clean['layout'] = $input['layout'];
		}

		// Checkbox: present = true, absent = false.
		$clean['show_author'] = ! empty( $input['show_author'] );

		return $clean;
	}

	// -----------------------------------------------------------------------
	// Settings page render
	// -----------------------------------------------------------------------

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( self::OPTION_KEY ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'ia_settings_group' );
				do_settings_sections( 'ia-settings' );
				submit_button( __( 'Save Settings', 'internal-announcements' ) );
				?>
			</form>
		</div>

		<script>
		( function () {
			var radios   = document.querySelectorAll( 'input[name="ia_settings[display_mode]"]' );
			var rowLimit = document.getElementById( 'ia-field-display-limit' );
			var rowDays  = document.getElementById( 'ia-field-display-days' );

			function toggle() {
				var mode = document.querySelector( 'input[name="ia_settings[display_mode]"]:checked' );
				if ( ! mode ) return;
				rowLimit.closest( 'tr' ).style.display = mode.value === 'fixed' ? '' : 'none';
				rowDays.closest( 'tr' ).style.display  = mode.value === 'days'  ? '' : 'none';
			}

			radios.forEach( function ( r ) { r.addEventListener( 'change', toggle ); } );
			toggle();
		} )();
		</script>
		<?php
	}

	// -----------------------------------------------------------------------
	// Admin assets
	// -----------------------------------------------------------------------

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'announcement_page_ia-settings' !== $hook ) {
			return;
		}
		// Inline script in render_page() handles all interaction; no extra files needed.
	}

	// -----------------------------------------------------------------------
	// Public API
	// -----------------------------------------------------------------------

	/**
	 * Return saved settings merged with defaults.
	 *
	 * Results are cached in a static property for the duration of the request
	 * so get_option() is only deserialized once per request.
	 *
	 * @return array{display_mode: string, display_limit: int, display_days: int, new_badge_days: int, layout: string, show_author: bool}
	 */
	public static function get(): array {
		if ( empty( self::$cache ) ) {
			$saved       = get_option( self::OPTION_KEY, array() );
			self::$cache = array_merge( self::DEFAULTS, is_array( $saved ) ? $saved : array() );
		}

		return self::$cache;
	}

	/**
	 * Build the meta_query clause that excludes expired announcements.
	 *
	 * Shows a post when its _expiry_date:
	 *   - does not exist (no expiry set), OR
	 *   - is an empty string (explicitly cleared), OR
	 *   - is >= today's date (still active).
	 *
	 * Extracted as a shared helper so any query for announcements can apply
	 * the same expiry logic without duplicating the clause.
	 */
	public static function build_expiry_meta_clause(): array {
		return array(
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
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
	}

	/**
	 * Return an inline style string for a category badge, based on the
	 * category's term_id modulo the palette size.
	 *
	 * Example return value: "background:#dbeafe;color:#1e40af;"
	 */
	public static function get_category_inline_style( int $term_id ): string {
		$palette = self::PALETTE;
		$pair    = $palette[ $term_id % count( $palette ) ];
		return sprintf( 'background:%s;color:%s;', $pair['bg'], $pair['color'] );
	}
}
