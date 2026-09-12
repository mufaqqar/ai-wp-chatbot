<?php
/**
 * Database schema and migration handling.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Responsible for creating, inspecting and upgrading plugin database tables.
 */
class Database {

	/**
	 * All plugin table definitions keyed by short name.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions(): array {
		return array(
			'conversations' => array( 'schema' => self::conversations_schema(), 'version' => '1.0.0' ),
			'messages'      => array( 'schema' => self::messages_schema(), 'version' => '1.0.0' ),
			'knowledge'     => array( 'schema' => self::knowledge_schema(), 'version' => '1.0.0' ),
			'faqs'          => array( 'schema' => self::faqs_schema(), 'version' => '1.0.0' ),
			'leads'         => array( 'schema' => self::leads_schema(), 'version' => '1.0.0' ),
			'logs'          => array( 'schema' => self::logs_schema(), 'version' => '1.0.0' ),
		);
	}

	/**
	 * Collation clause for new tables.
	 *
	 * @global \wpdb $wpdb
	 * @return string
	 */
	private static function collation(): string {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	/**
	 * Conversations table schema.
	 *
	 * @return string
	 */
	private static function conversations_schema(): string {
		$table = aiwc_table( 'conversations' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id varchar(32) NOT NULL,
			session_id varchar(64) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			started_at datetime NOT NULL,
			last_activity_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY conversation_id (conversation_id),
			KEY session_id (session_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY last_activity_at (last_activity_at)
		) " . self::collation() . ';';
	}

	/**
	 * Messages table schema.
	 *
	 * @return string
	 */
	private static function messages_schema(): string {
		$table = aiwc_table( 'messages' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id varchar(32) NOT NULL,
			role varchar(20) NOT NULL DEFAULT 'user',
			message longtext NOT NULL,
			sources text NULL,
			feedback tinyint(1) NOT NULL DEFAULT 0,
			had_fallback tinyint(1) NOT NULL DEFAULT 0,
			response_time float NOT NULL DEFAULT 0,
			usage_data text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id, created_at),
			KEY created_at (created_at)
		) " . self::collation() . ';';
	}

	/**
	 * Knowledge table schema.
	 *
	 * @return string
	 */
	private static function knowledge_schema(): string {
		$table = aiwc_table( 'knowledge' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_id varchar(64) NOT NULL DEFAULT '',
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			post_type varchar(32) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			url varchar(255) NOT NULL DEFAULT '',
			content longtext NOT NULL,
			content_hash varchar(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			metadata text NULL,
			indexed_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_id (source_id),
			KEY post_id (post_id),
			KEY post_type (post_type),
			KEY status (status),
			KEY content_hash (content_hash)
		) " . self::collation() . ';';
	}

	/**
	 * FAQ table schema.
	 *
	 * @return string
	 */
	private static function faqs_schema(): string {
		$table = aiwc_table( 'faqs' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question text NOT NULL,
			answer longtext NOT NULL,
			category varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY category (category)
		) " . self::collation() . ';';
	}

	/**
	 * Leads table schema.
	 *
	 * @return string
	 */
	private static function leads_schema(): string {
		$table = aiwc_table( 'leads' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id varchar(32) NOT NULL DEFAULT '',
			name varchar(100) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			phone varchar(50) NOT NULL DEFAULT '',
			message text NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			source varchar(32) NOT NULL DEFAULT 'chatbot',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id),
			KEY email (email),
			KEY status (status),
			KEY created_at (created_at)
		) " . self::collation() . ';';
	}

	/**
	 * Logs table schema.
	 *
	 * @return string
	 */
	private static function logs_schema(): string {
		$table = aiwc_table( 'logs' );

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			level varchar(20) NOT NULL DEFAULT 'info',
			event varchar(64) NOT NULL DEFAULT '',
			message text NULL,
			context text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY event (event),
			KEY created_at (created_at)
		) " . self::collation() . ';';
	}

	/**
	 * Create all database tables.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Ensure utf8mb4 for text fields on older MySQL versions.
		$wpdb->query( "SET SESSION sql_mode = ''" );

		foreach ( self::definitions() as $definition ) {
			dbDelta( $definition['schema'] );
		}

		update_option( 'aiwc_db_version', AIWC_DB_VERSION );
	}

	/**
	 * Drop all plugin tables.
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		foreach ( array_keys( self::definitions() ) as $name ) {
			$table = aiwc_table( $name );
			// Table name is generated internally from a fixed list, never from user input.
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Return a list of table names that currently exist in the database.
	 *
	 * @return array<string>
	 */
	public static function existing_tables(): array {
		global $wpdb;

		$existing = array();
		foreach ( array_keys( self::definitions() ) as $name ) {
			$table = aiwc_table( $name );
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $found ) {
				$existing[] = $name;
			}
		}

		return $existing;
	}

	/**
	 * Run schema upgrades when the stored database version differs.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$stored_version = (string) get_option( 'aiwc_db_version', '' );

		if ( version_compare( $stored_version, AIWC_DB_VERSION, '>=' ) ) {
			return;
		}

		self::create_tables();
	}
}