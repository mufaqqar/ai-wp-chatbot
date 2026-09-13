<?php
/**
 * Settings management and WordPress Settings API registration.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Centralised access point for plugin configuration. The API key is held in a
 * separate option so it can never leak through setting arrays.
 */
class Settings {

	public const OPTION_KEY    = 'aiwc_settings';
	public const API_KEY_OPTION = 'aiwc_api_key';

	/**
	 * Default plugin settings array.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'general'             => array(
				'enabled'             => true,
				'bot_name'            => __( 'AI Assistant', 'ai-website-chatbot' ),
				'welcome_message'     => __( 'Hi! 👋 How can I help you?', 'ai-website-chatbot' ),
				'fallback_message'    => __( "I'm sorry, I couldn't find that information. Would you like to contact our team?", 'ai-website-chatbot' ),
				'default_language'    => 'en',
				'collect_leads'       => true,
				'lead_prompt'         => __( 'Would you like to leave your details so we can contact you?', 'ai-website-chatbot' ),
			),
			'business'            => array(
				'business_name'        => get_bloginfo( 'name' ),
				'business_description' => '',
				'business_address'     => '',
				'phone'                => '',
				'email'                => '',
				'website_url'          => home_url( '/' ),
				'business_hours'       => '',
			),
			'instructions'        => array(
				'custom_instructions' => __( 'You are a helpful customer support assistant. Answer using only information available in the website knowledge base. Never invent facts, prices, policies or contact details.', 'ai-website-chatbot' ),
			),
			'ai'                  => array(
				'provider'  => 'openai',
				'model'     => 'gpt-4o-mini',
				'temperature' => 0.3,
				'max_tokens'  => 500,
				'timeout'     => 30,
				'retries'     => 1,
			),
			'knowledge'           => array(
				'retrieval_enabled'   => true,
				'auto_index'          => true,
				'show_sources'        => true,
				'retrieval_count'     => 5,
				'content_types'       => array( 'page', 'post' ),
				'index_custom_fields' => true,
				'meta_types'          => array(),
			),
			'woocommerce'         => array(
				'enabled'       => false,
				'show_prices'   => true,
				'show_stock'    => true,
				'show_links'    => true,
				'show_categories' => true,
			),
			'privacy'             => array(
				'store_conversations' => true,
				'retention_days'      => 0,
				'anonymize_visitors'  => true,
				'delete_on_uninstall' => false,
			),
			'rate_limits'         => array(
				'messages_per_minute' => 10,
				'messages_per_hour'   => 100,
				'max_message_length'  => 1000,
				'max_history_messages'=> 12,
				'daily_message_limit' => 500,
			),
			'appearance'          => array(
				'position'        => 'bottom-right',
				'primary_color'   => '#2563eb',
				'button_color'    => '#2563eb',
				'text_color'      => '#ffffff',
				'width'           => 380,
				'height'          => 560,
				'border_radius'   => 12,
				'button_icon'     => 'default',
				'custom_css'      => '',
			),
			'handoff'             => array(
				'enabled'              => true,
				'support_phone'        => '',
				'support_email'        => '',
				'whatsapp_link'        => '',
				'contact_form_enabled' => true,
			),
			'notifications'       => array(
				'enabled'              => false,
				'email'                => get_option( 'admin_email' ),
				'subject'              => __( 'New lead from AI Chatbot', 'ai-website-chatbot' ),
				'template'             => __( "A new lead was submitted:\n\nName: {name}\nEmail: {email}\nPhone: {phone}\nMessage: {message}", 'ai-website-chatbot' ),
			),
			'logging'             => array(
				'debug_logging' => false,
			),
		);
	}

	/**
	 * Return the full settings array.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		return aiwc_get_settings();
	}

	/**
	 * Return a single setting using dot notation.
	 *
	 * @param string $key     Dot-notated key.
	 * @param mixed  $default Default when missing.
	 * @return mixed
	 */
	public static function get_setting( string $key, $default = '' ) {
		return aiwc_get_setting( $key, $default );
	}

	/**
	 * Store the whole settings array.
	 *
	 * @param array $settings New settings array.
	 * @return void
	 */
	public static function update( array $settings ): void {
		$defaults = self::defaults();
		$final    = wp_parse_args( $settings, $defaults );
		update_option( self::OPTION_KEY, $final );
	}

	/**
	 * Set a single setting using dot notation.
	 *
	 * @param string $key   Dot-notated key.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public static function set( string $key, $value ): void {
		aiwc_set_setting( $key, $value );
	}

	/**
	 * Convert settings sections/fields into arrays with dot-notation keys.
	 *
	 * @param array $values Raw values in "section" => field => value form.
	 * @return array
	 */
	public static function normalize( array $values ): array {
		$settings = self::get();

		foreach ( $values as $section => $fields ) {
			if ( ! is_array( $fields ) ) {
				continue;
			}
			if ( ! isset( $settings[ $section ] ) || ! is_array( $settings[ $section ] ) ) {
				$settings[ $section ] = array();
			}
			foreach ( $fields as $key => $value ) {
				$settings[ $section ][ $key ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Sanitize a partial settings submission and merge it into the currently
	 * stored settings so that saving one page never destroys other sections.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return array
	 */
	public static function sanitize( $value ): array {
		$current = get_option( self::OPTION_KEY, array() );
		$current = is_array( $current ) ? $current : array();
		$raw     = is_array( $value ) ? $value : array();

		foreach ( $raw as $section => $fields ) {
			if ( ! is_array( $fields ) ) {
				continue;
			}
			$defaults             = isset( self::defaults()[ $section ] ) && is_array( self::defaults()[ $section ] ) ? self::defaults()[ $section ] : array();
			$current[ $section ]  = self::sanitize_section( $section, wp_parse_args( $fields, $defaults ) );
		}

		return $current;
	}

	/**
	 * Sanitize a single settings section.
	 *
	 * @param string $section Section name.
	 * @param array  $values  Section values.
	 * @return array
	 */
	private static function sanitize_section( string $section, array $values ): array {
		switch ( $section ) {
			case 'general':
				$values['enabled']          = (bool) $values['enabled'];
				$values['bot_name']         = sanitize_text_field( (string) $values['bot_name'] );
				$values['welcome_message']  = sanitize_text_field( (string) $values['welcome_message'] );
				$values['fallback_message'] = sanitize_textarea_field( (string) $values['fallback_message'] );
				$values['default_language'] = sanitize_text_field( (string) $values['default_language'] );
				$values['collect_leads']    = (bool) $values['collect_leads'];
				$values['lead_prompt']      = sanitize_text_field( (string) $values['lead_prompt'] );
				break;
			case 'business':
				$values['business_name']        = sanitize_text_field( (string) $values['business_name'] );
				$values['business_description'] = sanitize_textarea_field( (string) $values['business_description'] );
				$values['business_address']     = sanitize_textarea_field( (string) $values['business_address'] );
				$values['phone']                = sanitize_text_field( (string) $values['phone'] );
				$values['email']                = sanitize_email( (string) $values['email'] );
				$values['website_url']          = esc_url_raw( (string) $values['website_url'] );
				$values['business_hours']       = sanitize_textarea_field( (string) $values['business_hours'] );
				break;
			case 'instructions':
				$values['custom_instructions'] = sanitize_textarea_field( (string) $values['custom_instructions'] );
				break;
			case 'ai':
				$values['provider']    = in_array( $values['provider'], array( 'openai', 'openrouter' ), true ) ? $values['provider'] : 'openai';
				$values['model']       = sanitize_text_field( (string) $values['model'] );
				$values['temperature'] = min( 2.0, max( 0.0, (float) $values['temperature'] ) );
				$values['max_tokens']  = max( 1, (int) $values['max_tokens'] );
				$values['timeout']     = max( 5, (int) $values['timeout'] );
				$values['retries']     = max( 0, min( 5, (int) $values['retries'] ) );
				break;
			case 'knowledge':
				$values['retrieval_enabled'] = (bool) $values['retrieval_enabled'];
				$values['auto_index']        = (bool) $values['auto_index'];
				$values['show_sources']      = (bool) $values['show_sources'];
				$values['retrieval_count']   = max( 1, min( 20, (int) $values['retrieval_count'] ) );
				$values['content_types']       = array_map( 'sanitize_key', (array) $values['content_types'] );
				$values['index_custom_fields'] = (bool) $values['index_custom_fields'];
				$values['meta_types']          = array_map( 'sanitize_key', (array) $values['meta_types'] );
				break;
			case 'woocommerce':
				$values['enabled']          = (bool) $values['enabled'];
				$values['show_prices']      = (bool) $values['show_prices'];
				$values['show_stock']       = (bool) $values['show_stock'];
				$values['show_links']       = (bool) $values['show_links'];
				$values['show_categories']  = (bool) $values['show_categories'];
				break;
			case 'privacy':
				$values['store_conversations'] = (bool) $values['store_conversations'];
				$values['retention_days']      = max( 0, (int) $values['retention_days'] );
				$values['anonymize_visitors']  = (bool) $values['anonymize_visitors'];
				$values['delete_on_uninstall'] = (bool) $values['delete_on_uninstall'];
				break;
			case 'rate_limits':
				$values['messages_per_minute']   = max( 1, (int) $values['messages_per_minute'] );
				$values['messages_per_hour']     = max( 1, (int) $values['messages_per_hour'] );
				$values['max_message_length']    = max( 10, (int) $values['max_message_length'] );
				$values['max_history_messages']  = max( 2, min( 50, (int) $values['max_history_messages'] ) );
				$values['daily_message_limit']   = max( 1, (int) $values['daily_message_limit'] );
				break;
			case 'appearance':
				$values['position']      = in_array( $values['position'], array( 'bottom-right', 'bottom-left' ), true ) ? $values['position'] : 'bottom-right';
				$values['primary_color'] = sanitize_hex_color( (string) $values['primary_color'] ) ?: '#2563eb';
				$values['button_color']  = sanitize_hex_color( (string) $values['button_color'] ) ?: '#2563eb';
				$values['text_color']    = sanitize_hex_color( (string) $values['text_color'] ) ?: '#ffffff';
				$values['width']         = max( 300, min( 600, (int) $values['width'] ) );
				$values['height']        = max( 300, min( 900, (int) $values['height'] ) );
				$values['border_radius'] = max( 0, (int) $values['border_radius'] );
				$values['button_icon']   = sanitize_key( (string) $values['button_icon'] );
				$values['custom_css']    = sanitize_textarea_field( (string) $values['custom_css'] );
				break;
			case 'handoff':
				$values['enabled']              = (bool) $values['enabled'];
				$values['support_phone']        = sanitize_text_field( (string) $values['support_phone'] );
				$values['support_email']        = sanitize_email( (string) $values['support_email'] );
				$values['whatsapp_link']        = esc_url_raw( (string) $values['whatsapp_link'] );
				$values['contact_form_enabled'] = (bool) $values['contact_form_enabled'];
				break;
			case 'notifications':
				$values['enabled']  = (bool) $values['enabled'];
				$values['email']    = sanitize_email( (string) $values['email'] );
				$values['subject']  = sanitize_text_field( (string) $values['subject'] );
				$values['template'] = sanitize_textarea_field( (string) $values['template'] );
				break;
			case 'logging':
				$values['debug_logging'] = (bool) $values['debug_logging'];
				break;
		}

		return $values;
	}

	/**
	 * Install default settings if none exist.
	 *
	 * @return void
	 */
	public static function install_defaults(): void {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			update_option( self::OPTION_KEY, self::defaults() );
		}
	}

	/**
	 * Return the securely-stored API key for a provider.
	 *
	 * @param string $provider Provider slug (openai|openrouter). Empty uses the active provider.
	 * @return string
	 */
	public static function get_api_key( string $provider = '' ): string {
		if ( '' === $provider ) {
			$provider = (string) self::get_setting( 'ai.provider', 'openai' );
		}

		$key = (string) get_option( self::api_key_option( $provider ), '' );

		// Backwards compatibility with the original single-key option.
		if ( '' === $key && 'openai' === $provider ) {
			$key = (string) get_option( self::API_KEY_OPTION, '' );
		}

		return $key;
	}

	/**
	 * Store the API key in a provider-specific option.
	 *
	 * @param string $key      API key.
	 * @param string $provider Provider slug. Empty uses the active provider.
	 * @return void
	 */
	public static function set_api_key( string $key, string $provider = '' ): void {
		if ( '' === $provider ) {
			$provider = (string) self::get_setting( 'ai.provider', 'openai' );
		}

		update_option( self::api_key_option( $provider ), $key );

		// Keep the legacy option in sync for the OpenAI provider.
		if ( 'openai' === $provider ) {
			update_option( self::API_KEY_OPTION, $key );
		}
	}

	/**
	 * Option name that holds the key for a given provider.
	 *
	 * @param string $provider Provider slug.
	 * @return string
	 */
	public static function api_key_option( string $provider = '' ): string {
		if ( '' === $provider ) {
			$provider = (string) self::get_setting( 'ai.provider', 'openai' );
		}

		return 'openrouter' === $provider ? 'aiwc_api_key_openrouter' : 'aiwc_api_key_openai';
	}

	/**
	 * Whether the plugin is ready to make AI requests for a provider.
	 *
	 * @param string $provider Provider slug. Empty uses the active provider.
	 * @return bool
	 */
	public static function is_ai_configured( string $provider = '' ): bool {
		return '' !== self::get_api_key( $provider );
	}

	/**
	 * Register WordPress Settings API sections/fields for a group.
	 *
	 * @return void
	 */
	public static function register_rest_endpoint_schema(): void {
		register_setting(
			'aiwc_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}
}