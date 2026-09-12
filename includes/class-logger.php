<?php
/**
 * Optional debug logging.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Writes structured events into the plugin log table. Secrets are never logged.
 */
class Logger {

	/**
	 * Log an event when debug logging is enabled.
	 *
	 * @param string $level   Log level (info, warning, error).
	 * @param string $event   Machine-readable event name.
	 * @param string $message Human readable message.
	 * @param array  $context Optional non-secret context data.
	 * @return void
	 */
	public static function log( string $level, string $event, string $message, array $context = array() ): void {
		if ( ! (bool) Settings::get_setting( 'logging.debug_logging', false ) ) {
			return;
		}

		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'logs' ),
			array(
				'level'      => substr( $level, 0, 20 ),
				'event'      => substr( $event, 0, 64 ),
				'message'    => substr( $message, 0, 4000 ),
				'context'    => maybe_serialize( $context ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Convenience wrapper for error logging without a stack trace.
	 *
	 * @param string $event Event name.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public static function error( string $event, string $message, array $context = array() ): void {
		self::log( 'error', $event, $message, $context );
	}

	/**
	 * Convenience wrapper for info logging.
	 *
	 * @param string $event Event name.
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public static function info( string $event, string $message, array $context = array() ): void {
		self::log( 'info', $event, $message, $context );
	}

	/**
	 * Fetch recent log entries.
	 *
	 * @param int $limit Number of entries.
	 * @return array
	 */
	public static function fetch( int $limit = 100 ): array {
		global $wpdb;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM `" . aiwc_table( 'logs' ) . "` ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clear all log entries.
	 *
	 * @return int Rows deleted.
	 */
	public static function clear(): int {
		global $wpdb;

		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			'TRUNCATE TABLE `' . aiwc_table( 'logs' ) . '`'
		);
	}
}