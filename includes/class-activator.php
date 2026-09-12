<?php
/**
 * Plugin activation routine.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Handles everything that must happen on plugin activation.
 */
class Activator {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( plugin_basename( AIWC_FILE ) );
			wp_die( esc_html__( 'AI Website Chatbot requires PHP 8.1 or higher.', 'ai-website-chatbot' ) );
		}

		Database::maybe_upgrade();
		Settings::install_defaults();
		self::schedule_maintenance();
	}

	/**
	 * Schedule maintenance cron events.
	 *
	 * @return void
	 */
	private static function schedule_maintenance(): void {
		if ( ! wp_next_scheduled( 'aiwc_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'aiwc_maintenance' );
		}
	}
}