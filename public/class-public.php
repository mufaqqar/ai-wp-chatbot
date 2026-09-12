<?php
/**
 * Frontend asset loading and widget rendering.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the chat widget and handles the lightweight inline shortcode.
 */
class Frontend {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
		add_shortcode( 'ai_chatbot', array( $this, 'shortcode' ) );
		add_action( 'ai_chatbot_render', array( $this, 'render_widget' ) );
	}

	/**
	 * Register frontend CSS, JS and the global config object.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! (bool) Settings::get_setting( 'general.enabled', true ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'aiwc-chatbot',
			AIWC_URL . 'public/css/chatbot.css',
			array(),
			AIWC_VERSION
		);

		wp_enqueue_script(
			'aiwc-chatbot',
			AIWC_URL . 'public/js/chatbot.js',
			array(),
			AIWC_VERSION,
			true
		);

		$appearance = Settings::get_setting( 'appearance', array() );
		$handoff    = Settings::get_setting( 'handoff', array() );

		wp_localize_script(
			'aiwc-chatbot',
			'aiwcWidget',
			array(
				'restUrl' => esc_url_raw( rest_url( 'ai-chatbot/v1' ) ),
				'nonce'   => Security::create_nonce(),
				'config'  => array(
					'botName'     => (string) Settings::get_setting( 'general.bot_name', 'AI Assistant' ),
					'welcome'     => (string) Settings::get_setting( 'general.welcome_message', 'Hi! 👋 How can I help you?' ),
					'position'    => (string) Settings::get_setting( 'appearance.position', 'bottom-right' ),
					'primaryColor'=> (string) Settings::get_setting( 'appearance.primary_color', '#2563eb' ),
					'buttonColor' => (string) Settings::get_setting( 'appearance.button_color', '#2563eb' ),
					'textColor'   => (string) Settings::get_setting( 'appearance.text_color', '#ffffff' ),
					'width'       => (int) Settings::get_setting( 'appearance.width', 380 ),
					'height'      => (int) Settings::get_setting( 'appearance.height', 560 ),
					'borderRadius'=> (int) Settings::get_setting( 'appearance.border_radius', 12 ),
					'showSources' => (bool) Settings::get_setting( 'knowledge.show_sources', true ),
					'collectLeads'=> (bool) Settings::get_setting( 'general.collect_leads', true ),
					'storeConversations' => (bool) Settings::get_setting( 'privacy.store_conversations', true ),
					'leadPrompt'  => (string) Settings::get_setting( 'general.lead_prompt', '' ),
					'maxHistory'  => (int) Settings::get_setting( 'rate_limits.max_history_messages', 12 ),
					'handoff'     => array(
						'enabled'          => (bool) ( $handoff['enabled'] ?? true ),
						'support_phone'    => (string) ( $handoff['support_phone'] ?? '' ),
						'support_email'    => (string) ( $handoff['support_email'] ?? '' ),
						'whatsapp_link'    => (string) ( $handoff['whatsapp_link'] ?? '' ),
						'contact_form'     => (bool) ( $handoff['contact_form_enabled'] ?? true ),
					),
					'customCss'   => (string) Settings::get_setting( 'appearance.custom_css', '' ),
				),
			)
		);
	}

	/**
	 * Render the widget markup into wp_footer.
	 *
	 * @return void
	 */
	public function render_widget(): void {
		if ( ! (bool) Settings::get_setting( 'general.enabled', true ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		$custom_css = (string) Settings::get_setting( 'appearance.custom_css', '' );
		if ( '' !== $custom_css ) {
			echo '<style type="text/css">' . wp_strip_all_tags( $custom_css ) . '</style>' . "\n";
		}

		include AIWC_PATH . 'public/views/widget.php';
	}

	/**
	 * Shortcode handler: [ai_chatbot]
	 *
	 * @return string
	 */
	public function shortcode(): string {
		ob_start();
		$this->render_widget();

		return ob_get_clean();
	}
}