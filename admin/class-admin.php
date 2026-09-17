<?php
/**
 * Admin dashboard and page controllers.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot\Admin;

defined( 'ABSPATH' ) || exit;

use AIWebsiteChatbot\Settings;
use AIWebsiteChatbot\Leads;

/**
 * Registers the admin menu, render callbacks, assets and CSV export.
 */
class Admin {

	/**
	 * Capability required to administer the plugin.
	 *
	 * @var string
	 */
	private string $capability = 'manage_options';

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	private string $menu_slug = 'aiwc-dashboard';

	/**
	 * Initialize admin functionality.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_aiwc_export_leads', array( $this, 'export_leads' ) );
		add_action( 'admin_post_aiwc_save_api_key', array( $this, 'save_api_key' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the admin menu and submenus.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'AI Chatbot', 'ai-website-chatbot' ),
			__( 'AI Chatbot', 'ai-website-chatbot' ),
			$this->capability,
			$this->menu_slug,
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			30
		);

		$pages = array(
			'dashboard'     => array( __( 'Dashboard', 'ai-website-chatbot' ), $this->menu_slug ),
			'settings'      => array( __( 'Settings', 'ai-website-chatbot' ), 'aiwc-settings' ),
			'ai'            => array( __( 'AI Configuration', 'ai-website-chatbot' ), 'aiwc-ai' ),
			'knowledge'     => array( __( 'Knowledge Base', 'ai-website-chatbot' ), 'aiwc-knowledge' ),
			'faqs'          => array( __( 'FAQs', 'ai-website-chatbot' ), 'aiwc-faqs' ),
			'woocommerce'   => array( __( 'WooCommerce', 'ai-website-chatbot' ), 'aiwc-woocommerce' ),
			'conversations' => array( __( 'Conversations', 'ai-website-chatbot' ), 'aiwc-conversations' ),
			'leads'         => array( __( 'Leads', 'ai-website-chatbot' ), 'aiwc-leads' ),
			'analytics'     => array( __( 'Analytics', 'ai-website-chatbot' ), 'aiwc-analytics' ),
			'appearance'    => array( __( 'Appearance', 'ai-website-chatbot' ), 'aiwc-appearance' ),
			'help'          => array( __( 'Help', 'ai-website-chatbot' ), 'aiwc-help' ),
		);

		foreach ( $pages as $key => $page ) {
			$render = 'render_' . str_replace( '-', '_', $key );
			add_submenu_page(
				$this->menu_slug,
				$page[0],
				$page[0],
				$this->capability,
				$page[1],
				array( $this, $render )
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles only on plugin pages.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'aiwc-' ) && false === strpos( $hook, 'toplevel_page_aiwc' ) ) {
			return;
		}

		wp_enqueue_style( 'aiwc-admin', AIWC_URL . 'admin/css/admin.css', array(), AIWC_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'aiwc-admin', AIWC_URL . 'admin/js/admin.js', array( 'wp-util', 'wp-color-picker' ), AIWC_VERSION, true );

		wp_localize_script(
			'aiwc-admin',
			'aiwcAdmin',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'ai-chatbot/v1' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'exportUrl' => esc_url_raw( admin_url( 'admin-ajax.php?action=aiwc_export_leads&_wpnonce=' . wp_create_nonce( 'aiwc_export_leads' ) ) ),
				'messages' => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item?', 'ai-website-chatbot' ),
					'index_started'  => __( 'Indexing started.', 'ai-website-chatbot' ),
					'index_done'     => __( 'Indexing complete.', 'ai-website-chatbot' ),
					'saved'          => __( 'Settings saved.', 'ai-website-chatbot' ),
					'error'          => __( 'An error occurred. Please try again.', 'ai-website-chatbot' ),
				),
			)
		);
	}

	/**
	 * Register the single settings option group.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'aiwc_settings_group',
			Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
			)
		);
	}

	/**
	 * Render a view with the WP admin wrapper.
	 *
	 * @param string $view View name.
	 * @return void
	 */
	private function render( string $view ): void {
		$view_file = AIWC_PATH . 'admin/views/' . $view . '.php';
		if ( ! file_exists( $view_file ) ) {
			return;
		}
		include $view_file;
	}

	/**
	 * Dashboard page callback.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		$this->render( 'dashboard' );
	}

	/**
	 * Settings page callback.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		$this->render( 'settings' );
	}

	/**
	 * AI configuration page callback.
	 *
	 * @return void
	 */
	public function render_ai(): void {
		$this->render( 'ai' );
	}

	/**
	 * Knowledge base page callback.
	 *
	 * @return void
	 */
	public function render_knowledge(): void {
		$this->render( 'knowledge' );
	}

	/**
	 * FAQs page callback.
	 *
	 * @return void
	 */
	public function render_faqs(): void {
		$this->render( 'faqs' );
	}

	/**
	 * WooCommerce page callback.
	 *
	 * @return void
	 */
	public function render_woocommerce(): void {
		$this->render( 'woocommerce' );
	}

	/**
	 * Conversations page callback.
	 *
	 * @return void
	 */
	public function render_conversations(): void {
		$this->render( 'conversations' );
	}

	/**
	 * Leads page callback.
	 *
	 * @return void
	 */
	public function render_leads(): void {
		$this->render( 'leads' );
	}

	/**
	 * Analytics page callback.
	 *
	 * @return void
	 */
	public function render_analytics(): void {
		$this->render( 'analytics' );
	}

	/**
	 * Appearance page callback.
	 *
	 * @return void
	 */
	public function render_appearance(): void {
		$this->render( 'appearance' );
	}

	/**
	 * Help page callback.
	 *
	 * @return void
	 */
	public function render_help(): void {
		$this->render( 'help' );
	}

	/**
	 * Handle lead CSV export.
	 *
	 * @return void
	 */
	public function export_leads(): void {
		Leads::export_csv();
	}

	/**
	 * Save the AI provider API key from the dedicated admin post form.
	 *
	 * @return void
	 */
	public function save_api_key(): void {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You are not allowed to change this setting.', 'ai-website-chatbot' ), 403 );
		}

		check_admin_referer( 'aiwc_save_api_key' );

		$key      = trim( (string) sanitize_text_field( wp_unslash( $_POST['aiwc_api_key'] ?? '' ) ) );
		$provider = sanitize_text_field( wp_unslash( $_POST['aiwc_provider'] ?? '' ) );
		Settings::set_api_key( $key, '' !== $provider ? $provider : '' );

		wp_safe_redirect( admin_url( 'admin.php?page=aiwc-ai&aiwc_saved=1' ) );
		exit;
	}
}