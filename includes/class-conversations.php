<?php
/**
 * Conversation and message storage.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Persists conversations and their messages. Raw IP addresses are never stored.
 */
class Conversations {

	/**
	 * Start or resume a conversation.
	 *
	 * @param string $conversation_id Existing conversation id or empty for new.
	 * @param string $session_id      Anonymous session identifier.
	 * @param int    $user_id         Logged in user id or 0.
	 * @return array Conversation row.
	 */
	public static function start( string $conversation_id = '', string $session_id = '', int $user_id = 0 ): array {
		global $wpdb;

		$now = current_time( 'mysql' );

		if ( '' !== $conversation_id ) {
			$existing = self::get_by_conversation_id( $conversation_id );
			if ( $existing ) {
				self::touch( $existing['id'], $now );

				return $existing;
			}
		}

		if ( '' === $conversation_id ) {
			$conversation_id = aiwc_generate_id( 32 );
		}

		$session_id = '' !== $session_id ? substr( $session_id, 0, 64 ) : aiwc_generate_id( 16 );
		$data       = array(
			'conversation_id'  => $conversation_id,
			'session_id'       => $session_id,
			'user_id'          => max( 0, (int) $user_id ),
			'status'           => 'active',
			'started_at'       => $now,
			'last_activity_at' => $now,
			'created_at'       => $now,
			'updated_at'       => $now,
		);

		$wpdb->insert( aiwc_table( 'conversations' ), $data, array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return self::get_by_conversation_id( $conversation_id );
	}

	/**
	 * Update activity timestamps by public conversation id.
	 *
	 * @param string $conversation_id Conversation id.
	 * @return void
	 */
	public static function touch_by_conversation_id( string $conversation_id ): void {
		global $wpdb;

		$now = current_time( 'mysql' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'conversations' ),
			array( 'last_activity_at' => $now, 'updated_at' => $now ),
			array( 'conversation_id' => $conversation_id ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Update activity timestamps on a conversation.
	 *
	 * @param int    $id      Row id.
	 * @param string $at      Date time string.
	 * @return void
	 */
	public static function touch( int $id, string $at = '' ): void {
		global $wpdb;

		$at = '' !== $at ? $at : current_time( 'mysql' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'conversations' ),
			array( 'last_activity_at' => $at, 'updated_at' => $at ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get a conversation by its public conversation id.
	 *
	 * @param string $conversation_id Conversation id.
	 * @return array|null
	 */
	public static function get_by_conversation_id( string $conversation_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM `' . aiwc_table( 'conversations' ) . '` WHERE conversation_id = %s LIMIT 1',
				$conversation_id
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Add a message to a conversation.
	 *
	 * @param string $conversation_id Conversation id.
	 * @param string $role            user|assistant|system.
	 * @param string $message         Message body.
	 * @param array  $extra           Optional metadata (sources, response_time, usage_data, had_fallback).
	 * @return int Inserted row id.
	 */
	public static function add_message( string $conversation_id, string $role, string $message, array $extra = array() ): int {
		global $wpdb;

		$data = array(
			'conversation_id' => $conversation_id,
			'role'            => in_array( $role, array( 'user', 'assistant', 'system' ), true ) ? $role : 'user',
			'message'         => $message,
			'sources'         => isset( $extra['sources'] ) ? wp_json_encode( $extra['sources'] ) : '',
			'feedback'        => 0,
			'had_fallback'    => ! empty( $extra['had_fallback'] ) ? 1 : 0,
			'response_time'   => isset( $extra['response_time'] ) ? (float) $extra['response_time'] : 0,
			'usage_data'      => isset( $extra['usage_data'] ) ? wp_json_encode( $extra['usage_data'] ) : '',
			'created_at'      => current_time( 'mysql' ),
		);

		$wpdb->insert( aiwc_table( 'messages' ), $data, array( '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve recent message history for a conversation.
	 *
	 * @param string $conversation_id Conversation id.
	 * @param int    $limit           Maximum number of messages (defaults to configured history length).
	 * @return array
	 */
	public static function history( string $conversation_id, int $limit = 0 ): array {
		global $wpdb;

		if ( $limit <= 0 ) {
			$limit = (int) Settings::get_setting( 'rate_limits.max_history_messages', 12 );
		}

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT role, message FROM `' . aiwc_table( 'messages' ) . '` WHERE conversation_id = %s AND role IN (%s, %s) ORDER BY id DESC LIMIT %d',
				$conversation_id,
				'user',
				'assistant',
				$limit
			),
			ARRAY_A
		);

		return array_reverse( (array) $rows );
	}

	/**
	 * Count messages in a conversation.
	 *
	 * @param string $conversation_id Conversation id.
	 * @return int
	 */
	public static function count_messages( string $conversation_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . aiwc_table( 'messages' ) . '` WHERE conversation_id = %s',
				$conversation_id
			)
		);
	}

	/**
	 * Delete a conversation and all its messages.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	public static function delete( int $id ): void {
		global $wpdb;

		$conversation = self::get( $id );
		if ( ! $conversation ) {
			return;
		}

		$wpdb->delete( aiwc_table( 'messages' ), array( 'conversation_id' => $conversation['conversation_id'] ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( aiwc_table( 'conversations' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( aiwc_table( 'leads' ), array( 'conversation_id' => $conversation['conversation_id'] ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Fetch a single conversation by row id.
	 *
	 * @param int $id Row id.
	 * @return array|null
	 */
	public static function get( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM `' . aiwc_table( 'conversations' ) . '` WHERE id = %d LIMIT 1',
				$id
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * List conversations with message counts and pagination.
	 *
	 * @param int    $page   Page number.
	 * @param int    $per_page Items per page.
	 * @param string $search Optional search term.
	 * @return array{items:array,total:int,pages:int}
	 */
	public static function list_all( int $page = 1, int $per_page = 20, string $search = '' ): array {
		global $wpdb;

		$table_c = aiwc_table( 'conversations' );
		$table_m = aiwc_table( 'messages' );
		$page    = max( 1, $page );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset  = ( $page - 1 ) * $per_page;
		$where   = '';
		$params  = array( $per_page, $offset );

		if ( '' !== $search ) {
			$where  = 'WHERE c.conversation_id LIKE %s';
			$params = array_merge( array( '%' . $wpdb->esc_like( $search ) . '%' ), $params );
		}

		$sql = "SELECT c.*, (SELECT COUNT(*) FROM {$table_m} m WHERE m.conversation_id = c.conversation_id) AS message_count
			FROM {$table_c} c {$where}
			ORDER BY c.last_activity_at DESC
			LIMIT %d OFFSET %d";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_c} c {$where}", $where ? array( $params[0] ) : array() ) ); // phpcs:ignore WordPress.DB

		return array(
			'items' => (array) $items,
			'total' => $total,
			'pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	/**
	 * Delete conversations older than the retention period.
	 *
	 * @param int $days Days to keep.
	 * @return void
	 */
	public static function purge_old( int $days ): void {
		global $wpdb;

		if ( $days <= 0 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );

		$ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT id FROM `' . aiwc_table( 'conversations' ) . '` WHERE last_activity_at < %s',
				$cutoff
			)
		);

		foreach ( (array) $ids as $id ) {
			self::delete( (int) $id );
		}
	}
}