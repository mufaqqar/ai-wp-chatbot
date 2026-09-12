<?php
/**
 * Analytics and reporting.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates lightweight usage statistics for the dashboard.
 */
class Analytics {

	/**
	 * Return the full statistics payload.
	 *
	 * @return array
	 */
	public static function stats(): array {
		global $wpdb;

		$conversations = aiwc_table( 'conversations' );
		$messages      = aiwc_table( 'messages' );

		$now           = current_time( 'mysql' );
		$start_today   = gmdate( 'Y-m-d 00:00:00', strtotime( $now ) );
		$start_week    = gmdate( 'Y-m-d 00:00:00', strtotime( 'monday this week', strtotime( $now ) ) );
		$start_month   = gmdate( 'Y-m-01 00:00:00', strtotime( $now ) );

		$total_conversations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$conversations}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$today               = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$conversations}` WHERE created_at >= %s", $start_today ) ); // phpcs:ignore WordPress.DB
		$week                = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$conversations}` WHERE created_at >= %s", $start_week ) ); // phpcs:ignore WordPress.DB
		$month               = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$conversations}` WHERE created_at >= %s", $start_month ) ); // phpcs:ignore WordPress.DB

		$total_messages   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$messages}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$user_messages    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$messages}` WHERE role = %s", 'user' ) ); // phpcs:ignore WordPress.DB
		$assistant_msgs   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$messages}` WHERE role = %s", 'assistant' ) ); // phpcs:ignore WordPress.DB
		$failed           = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$messages}` WHERE had_fallback = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$answered         = max( 0, $assistant_msgs - $failed );

		$avg_response = (float) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(response_time) FROM `{$messages}` WHERE role = %s AND response_time > 0", 'assistant' ) ); // phpcs:ignore WordPress.DB

		$avg_length = $total_conversations > 0 ? round( $total_messages / $total_conversations, 1 ) : 0;

		$tokens = 0;
		$usage_rows = $wpdb->get_col( "SELECT usage_data FROM `{$messages}` WHERE usage_data != '' LIMIT 1000" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $usage_rows as $usage_json ) {
			$usage = json_decode( (string) $usage_json, true );
			if ( is_array( $usage ) && isset( $usage['total_tokens'] ) ) {
				$tokens += (int) $usage['total_tokens'];
			}
		}

		$estimated_cost = round( $tokens * 0.0000015, 4 );

		$popular = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT message, COUNT(*) AS total FROM `{$messages}` WHERE role = %s GROUP BY message ORDER BY total DESC LIMIT 10",
				'user'
			),
			ARRAY_A
		);

		$failed_questions = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT m.message AS question, COUNT(*) AS total
				FROM `{$messages}` m
				INNER JOIN `{$messages}` a ON a.conversation_id = m.conversation_id AND a.role = %s AND a.had_fallback = 1
				WHERE m.role = %s
				GROUP BY m.message ORDER BY total DESC LIMIT 10",
				'assistant',
				'user'
			),
			ARRAY_A
		);

		return array(
			'total_conversations' => $total_conversations,
			'conversations_today' => $today,
			'conversations_week'  => $week,
			'conversations_month' => $month,
			'total_messages'      => $total_messages,
			'user_messages'       => $user_messages,
			'leads'               => Leads::count(),
			'answered'            => $answered,
			'failed'              => $failed,
			'avg_length'          => $avg_length,
			'avg_response_time'   => round( $avg_response, 2 ),
			'tokens'              => $tokens,
			'estimated_cost'      => $estimated_cost,
			'daily'               => self::daily_series( 14 ),
			'popular'             => (array) $popular,
			'failed_questions'    => (array) $failed_questions,
		);
	}

	/**
	 * Daily conversation and message counts for the last N days.
	 *
	 * @param int $days Number of days.
	 * @return array
	 */
	public static function daily_series( int $days = 14 ): array {
		global $wpdb;

		$conversations = aiwc_table( 'conversations' );
		$messages      = aiwc_table( 'messages' );
		$series        = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$day          = gmdate( 'Y-m-d', strtotime( "-{$i} days", current_time( 'timestamp' ) ) );
			$day_start    = $day . ' 00:00:00';
			$day_end      = $day . ' 23:59:59';

			$convs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$conversations}` WHERE created_at BETWEEN %s AND %s", $day_start, $day_end ) ); // phpcs:ignore WordPress.DB
			$msgs  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$messages}` WHERE created_at BETWEEN %s AND %s", $day_start, $day_end ) ); // phpcs:ignore WordPress.DB

			$series[] = array(
				'date'          => $day,
				'conversations' => $convs,
				'messages'      => $msgs,
			);
		}

		return $series;
	}
}