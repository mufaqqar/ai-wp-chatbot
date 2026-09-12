<?php
/**
 * Main plugin orchestration class.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates dependency loading, hook registration and module initialization.
 * Contains no business logic of its own.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->load_textdomain();
		$this->upgrade_check();
		$this->register_hooks();
		$this->init_modules();
	}

	/**
	 * Register global hooks and filters.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'aiwc_maintenance', array( $this, 'maintenance' ) );

		if ( is_admin() ) {
			require_once AIWC_PATH . 'admin/class-admin.php';
			$admin = new Admin\Admin();
			$admin->init();
		} else {
			require_once AIWC_PATH . 'public/class-public.php';
			$frontend = new Frontend();
			$frontend->init();
		}
	}

	/**
	 * Register the REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		require_once AIWC_PATH . 'includes/class-rest-api.php';
		( new REST_API() )->register_routes();
	}

	/**
	 * Initialize modules that hook into core lifecycle events.
	 *
	 * @return void
	 */
	private function init_modules(): void {
		if ( (bool) Settings::get_setting( 'knowledge.auto_index', true ) ) {
			Content_Indexer::register_hooks();
		}
	}

	/**
	 * Run database migrations when needed.
	 *
	 * @return void
	 */
	private function upgrade_check(): void {
		$installed = (string) get_option( 'aiwc_db_version', '' );
		if ( version_compare( $installed, AIWC_DB_VERSION, '<' ) ) {
			Database::maybe_upgrade();
		}

		if ( version_compare( (string) get_option( 'aiwc_version', '' ), AIWC_VERSION, '<' ) ) {
			update_option( 'aiwc_version', AIWC_VERSION );
		}
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain( 'ai-website-chatbot', false, dirname( AIWC_BASENAME ) . '/languages' );
	}

	/**
	 * Hourly maintenance: retention cleanup and log pruning.
	 *
	 * @return void
	 */
	public function maintenance(): void {
		$retention = (int) Settings::get_setting( 'privacy.retention_days', 0 );
		if ( $retention > 0 ) {
			Conversations::purge_old( $retention );
		}

		// Prune logs older than 90 days.
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM `' . aiwc_table( 'logs' ) . '` WHERE created_at < %s', $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( (bool) Settings::get_setting( 'knowledge.auto_index', true ) ) {
			Content_Indexer::process_dirty_posts();
		}
	}
}