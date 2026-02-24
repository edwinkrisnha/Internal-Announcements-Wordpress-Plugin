<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Announcement_Read_Tracker {

	// -----------------------------------------------------------------------
	// Table management
	// -----------------------------------------------------------------------

	/**
	 * Return the full table name (with wpdb prefix).
	 */
	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . IA_TABLE_READS;
	}

	/**
	 * Create the reads table via dbDelta.
	 * Safe to call on every activation — dbDelta is idempotent.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$table}` (
			id      bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			read_at datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_post  (user_id, post_id),
			KEY        idx_user   (user_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// -----------------------------------------------------------------------
	// Write
	// -----------------------------------------------------------------------

	/**
	 * Mark an announcement as read for a user.
	 *
	 * INSERT IGNORE means repeat calls are safe — no exception, no duplicate row.
	 */
	public static function mark_read( int $user_id, int $post_id ): void {
		global $wpdb;

		$table = self::table();

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `{$table}` (user_id, post_id) VALUES (%d, %d)",
				$user_id,
				$post_id
			)
		);
	}

	// -----------------------------------------------------------------------
	// Read
	// -----------------------------------------------------------------------

	/**
	 * Return all post IDs read by a given user.
	 *
	 * Use this in feed loops — one query, then in_array() — to avoid N+1.
	 *
	 * @return int[]
	 */
	public static function get_read_post_ids( int $user_id ): array {
		global $wpdb;

		$table = self::table();

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM `{$table}` WHERE user_id = %d",
				$user_id
			)
		);

		return array_map( 'intval', $rows );
	}

	/**
	 * Check whether a single announcement has been read by a user.
	 *
	 * Prefer get_read_post_ids() + in_array() when iterating a feed.
	 * This method issues its own query and should only be used in isolation.
	 */
	public static function is_read( int $user_id, int $post_id ): bool {
		global $wpdb;

		$table = self::table();

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE user_id = %d AND post_id = %d",
				$user_id,
				$post_id
			)
		);

		return $count > 0;
	}

	/**
	 * Count unread published announcements for a user.
	 *
	 * Used to display the unread badge on the announcements page.
	 */
	public static function get_unread_count( int $user_id ): int {
		global $wpdb;

		$table       = self::table();
		$posts_table = $wpdb->posts;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				   FROM `{$posts_table}`
				  WHERE post_type   = 'announcement'
				    AND post_status = 'publish'
				    AND ID NOT IN (
				            SELECT post_id
				              FROM `{$table}`
				             WHERE user_id = %d
				        )",
				$user_id
			)
		);
	}
}
