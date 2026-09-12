<?php
/**
 * Helper functions for the AI Website Chatbot plugin.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the compiled plugin settings (defaults merged with saved values).
 *
 * @return array<string,mixed>
 */
function aiwc_get_settings(): array {
	$defaults = apply_filters( 'ai_chatbot_default_settings', AIWebsiteChatbot\Settings::defaults() );
	$saved    = get_option( 'aiwc_settings', array() );

	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * Retrieve a single plugin setting.
 *
 * @param string $key     Setting key. Supports dot notation for nested values.
 * @param mixed  $default Default value to return when the key does not exist.
 * @return mixed
 */
function aiwc_get_setting( string $key, $default = '' ) {
	$settings = aiwc_get_settings();
	$keys     = explode( '.', $key );
	$value    = $settings;

	foreach ( $keys as $segment ) {
		if ( is_array( $value ) && array_key_exists( $segment, $value ) ) {
			$value = $value[ $segment ];
		} else {
			return $default;
		}
	}

	return $value;
}

/**
 * Update a single setting by key (dot notation supported).
 *
 * @param string $key   Setting key.
 * @param mixed  $value New value.
 * @return void
 */
function aiwc_set_setting( string $key, $value ): void {
	$settings = aiwc_get_settings();
	$keys     = explode( '.', $key );
	$node     = &$settings;

	foreach ( $keys as $segment ) {
		if ( ! isset( $node[ $segment ] ) || ! is_array( $node[ $segment ] ) ) {
			$node[ $segment ] = array();
		}
		$node = &$node[ $segment ];
	}

	$node = $value;
	update_option( 'aiwc_settings', $settings );
}

/**
 * Return a fully prefixed database table name.
 *
 * @param string $name Short table name without prefix (e.g. "conversations").
 * @return string
 */
function aiwc_table( string $name ): string {
	global $wpdb;

	return $wpdb->prefix . 'aiwc_' . $name;
}

/**
 * Return the list of plugin table definitions.
 *
 * @return array<string,array<string,mixed>> Table name => structure array.
 */
function aiwc_tables() {
	return AIWebsiteChatbot\Database::definitions();
}

/**
 * Whether WooCommerce is active.
 *
 * @return bool
 */
function aiwc_is_woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Return the plugin version.
 *
 * @return string
 */
function aiwc_version(): string {
	return defined( 'AIWC_VERSION' ) ? AIWC_VERSION : '1.0.0';
}

/**
 * Clean arbitrary text input for safe storage.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function aiwc_clean_text( $value ): string {
	return sanitize_text_field( wp_unslash( (string) $value ) );
}

/**
 * Generate a random identifier.
 *
 * @param int $length Desired length.
 * @return string
 */
function aiwc_generate_id( int $length = 32 ): string {
	return substr( bin2hex( random_bytes( 64 ) ), 0, $length );
}

/**
 * Strip shortcodes and script/style tags from content, then collapse whitespace.
 *
 * @param string $content Raw content.
 * @return string
 */
function aiwc_normalize_content( string $content ): string {
	$content = strip_shortcodes( $content );
	$content = preg_replace( '#<script[^>]*>.*?</script>#is', ' ', $content );
	$content = preg_replace( '#<style[^>]*>.*?</style>#is', ' ', $content );
	$content = wp_strip_all_tags( $content );
	$content = html_entity_decode( $content, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$content = preg_replace( '/\s+/u', ' ', $content );

	return trim( (string) $content );
}

/**
 * Check whether the current request is targeting the site frontend.
 *
 * @return bool
 */
function aiwc_is_frontend_request(): bool {
	return ! is_admin() && ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}