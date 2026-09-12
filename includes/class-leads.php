<?php
/**
 * Lead capture and management.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Stores leads submitted through the chatbot and handles notifications/export.
 */
class Leads {

	/**
	 * Valid lead statuses.
	 *
	 * @return array
	 */
	public static function statuses(): array {
		return array( 'new', 'contacted', 'qualified', 'converted', 'closed' );
	}

	/**
	 * Create a lead.
	 *
	 * @param array $data Lead data: name, email, phone, message, conversation_id, source.
	 * @return int|\WP_Error Inserted id or error.
	 */
	public static function add( array $data ) {
		global $wpdb;

		$name    = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$phone   = sanitize_text_field( (string) ( $data['phone'] ?? '' ) );
		$message = sanitize_textarea_field( (string) ( $data['message'] ?? '' ) );

		if ( '' === $name || '' === $email || ! is_email( $email ) ) {
			return new \WP_Error( 'aiwc_invalid_lead', __( 'Please provide a valid name and email address.', 'ai-website-chatbot' ), array( 'status' => 400 ) );
		}

		$now = current_time( 'mysql' );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'leads' ),
			array(
				'conversation_id' => substr( sanitize_text_field( (string) ( $data['conversation_id'] ?? '' ) ), 0, 32 ),
				'name'            => substr( $name, 0, 100 ),
				'email'           => substr( $email, 0, 190 ),
				'phone'           => substr( $phone, 0, 50 ),
				'message'         => $message,
				'status'          => 'new',
				'source'          => substr( sanitize_key( (string) ( $data['source'] ?? 'chatbot' ) ), 0, 32 ),
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$lead_id = (int) $wpdb->insert_id;

		$lead = array(
			'id'    => $lead_id,
			'name'  => $name,
			'email' => $email,
			'phone' => $phone,
			'message' => $message,
		);

		/**
		 * Fires after a lead has been stored.
		 *
		 * @param array $lead Lead data.
		 */
		do_action( 'ai_chatbot_after_lead', $lead );

		self::notify( $lead );

		return $lead_id;
	}

	/**
	 * Send an email notification for a new lead.
	 *
	 * @param array $lead Lead data.
	 * @return void
	 */
	private static function notify( array $lead ): void {
		if ( ! (bool) Settings::get_setting( 'notifications.enabled', false ) ) {
			return;
		}

		$to = (string) Settings::get_setting( 'notifications.email', get_option( 'admin_email' ) );
		if ( ! is_email( $to ) ) {
			return;
		}

		$subject = (string) Settings::get_setting( 'notifications.subject', __( 'New lead from AI Chatbot', 'ai-website-chatbot' ) );
		$template = (string) Settings::get_setting( 'notifications.template', '' );

		$replacements = array(
			'{name}'    => $lead['name'],
			'{email}'   => $lead['email'],
			'{phone}'   => $lead['phone'],
			'{message}' => $lead['message'],
		);

		$body = strtr( $template, $replacements );

		wp_mail( $to, $subject, $body );
	}

	/**
	 * List leads with filtering and pagination.
	 *
	 * @param array $args page, per_page, status, search.
	 * @return array{items:array,total:int,pages:int}
	 */
	public static function list_all( array $args = array() ): array {
		global $wpdb;

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 200, (int) ( $args['per_page'] ?? 20 ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		$where    = array();
		$params   = array();

		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::statuses(), true ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(name LIKE %s OR email LIKE %s OR phone LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$table     = aiwc_table( 'leads' );

		$list_params = array_merge( $params, array( $per_page, $offset ) );

		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d", $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` {$where_sql}", $params ) ); // phpcs:ignore WordPress.DB

		return array(
			'items' => (array) $items,
			'total' => $total,
			'pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	/**
	 * Update a lead status.
	 *
	 * @param int    $id     Lead id.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function update_status( int $id, string $status ): bool {
		global $wpdb;

		if ( ! in_array( $status, self::statuses(), true ) ) {
			return false;
		}

		return (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'leads' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete a lead.
	 *
	 * @param int $id Lead id.
	 * @return void
	 */
	public static function delete( int $id ): void {
		global $wpdb;

		$wpdb->delete( aiwc_table( 'leads' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Count leads.
	 *
	 * @return int
	 */
	public static function count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . aiwc_table( 'leads' ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Export all leads as CSV and terminate the request.
	 *
	 * @return void
	 */
	public static function export_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export leads.', 'ai-website-chatbot' ), 403 );
		}

		check_admin_referer( 'aiwc_export_leads' );

		global $wpdb;

		$rows = $wpdb->get_results( 'SELECT * FROM `' . aiwc_table( 'leads' ) . '` ORDER BY created_at DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=aiwc-leads-' . gmdate( 'Ymd-His' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Conversation', 'Name', 'Email', 'Phone', 'Message', 'Status', 'Source', 'Created' ) );

		foreach ( (array) $rows as $row ) {
			fputcsv(
				$output,
				array(
					$row['id'],
					$row['conversation_id'],
					$row['name'],
					$row['email'],
					$row['phone'],
					$row['message'],
					$row['status'],
					$row['source'],
					$row['created_at'],
				)
			);
		}

		fclose( $output );
		exit;
	}
}