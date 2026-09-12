<?php
/**
 * AI Website Chatbot — uninstall routine.
 *
 * Data is only removed if the site administrator explicitly enabled
 * "Delete all plugin data on uninstall" in the plugin settings.
 *
 * @package AIWebsiteChatbot
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'aiwc_settings', array() );
$settings = is_array( $settings ) ? $settings : array();

$delete = (bool) ( $settings['privacy']['delete_on_uninstall'] ?? false );

if ( ! $delete ) {
	return;
}

global $wpdb;

// Remove scheduled events.
wp_clear_scheduled_hook( 'aiwc_maintenance' );

// Drop plugin tables.
$tables = array(
	'aiwc_conversations',
	'aiwc_messages',
	'aiwc_knowledge',
	'aiwc_faqs',
	'aiwc_leads',
	'aiwc_logs',
);

foreach ( $tables as $table ) {
	$full = $wpdb->prefix . $table;
	// Table name is an internally generated constant, never user input.
	$wpdb->query( "DROP TABLE IF EXISTS `{$full}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

// Remove options.
$options = array(
	'aiwc_settings',
	'aiwc_api_key',
	'aiwc_db_version',
	'aiwc_version',
	'aiwc_index_progress',
	'aiwc_daily_counts',
	'aiwc_fingerprint_salt',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Remove transient counterparts and rate-limit transients.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", 'aiwc_rl_%', '_transient_aiwc_%', '_transient_timeout_aiwc_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery