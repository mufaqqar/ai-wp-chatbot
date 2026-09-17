<?php
/**
 * Knowledge base storage and retrieval.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Stores indexed website content and performs keyword-based retrieval that can
 * later be swapped for a vector database through the ai_chatbot_retrieve_knowledge filter.
 */
class Knowledge_Base {

	/**
	 * Upsert a knowledge entry by source_id.
	 *
	 * @param array $data Entry data.
	 * @return int Row id.
	 */
	public static function upsert( array $data ): int {
		global $wpdb;

		$existing = self::get_source( $data['source_id'] ?? '' );

		$row = array(
			'source_id'    => substr( $data['source_id'] ?? '', 0, 64 ),
			'post_id'      => (int) ( $data['post_id'] ?? 0 ),
			'post_type'    => substr( $data['post_type'] ?? '', 0, 32 ),
			'title'        => substr( $data['title'] ?? '', 0, 255 ),
			'url'          => substr( $data['url'] ?? '', 0, 255 ),
			'content'      => (string) ( $data['content'] ?? '' ),
			'content_hash' => (string) ( $data['content_hash'] ?? '' ),
			'status'       => 'active',
			'metadata'     => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : '',
			'indexed_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		if ( $existing ) {
			$row['created_at'] = $existing['created_at'];
			$wpdb->update( aiwc_table( 'knowledge' ), $row, array( 'id' => $existing['id'] ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			return (int) $existing['id'];
		}

		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( aiwc_table( 'knowledge' ), $row, array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get an entry by source id.
	 *
	 * @param string $source_id Source identifier.
	 * @return array|null
	 */
	public static function get_source( string $source_id ): ?array {
		global $wpdb;

		if ( '' === $source_id ) {
			return null;
		}

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM `' . aiwc_table( 'knowledge' ) . '` WHERE source_id = %s LIMIT 1',
				$source_id
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Get an entry by WordPress post id and post type.
	 *
	 * @param int    $post_id  Post id.
	 * @param string $post_type Post type.
	 * @return array|null
	 */
	public static function get_by_post( int $post_id, string $post_type ): ?array {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM `' . aiwc_table( 'knowledge' ) . '` WHERE post_id = %d AND post_type = %s LIMIT 1',
				$post_id,
				$post_type
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Delete an entry.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	public static function delete( int $id ): void {
		global $wpdb;

		$wpdb->delete( aiwc_table( 'knowledge' ), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete an entry by source id.
	 *
	 * @param string $source_id Source identifier.
	 * @return void
	 */
	public static function delete_source( string $source_id ): void {
		global $wpdb;

		if ( '' === $source_id ) {
			return;
		}

		$wpdb->delete( aiwc_table( 'knowledge' ), array( 'source_id' => $source_id ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete entries for a given post.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function delete_by_post( int $post_id ): void {
		global $wpdb;

		$wpdb->delete( aiwc_table( 'knowledge' ), array( 'post_id' => $post_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete entries by post type.
	 *
	 * @param string $post_type Post type key.
	 * @return int Number of rows deleted.
	 */
	public static function delete_by_post_type( string $post_type ): int {
		global $wpdb;

		if ( '' === $post_type ) {
			return 0;
		}

		$deleted = $wpdb->delete( aiwc_table( 'knowledge' ), array( 'post_type' => $post_type ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $deleted;
	}

	/**
	 * Delete entries by a list of row ids.
	 *
	 * @param array<int> $ids Row ids.
	 * @return int Number of rows deleted.
	 */
	public static function delete_many( array $ids ): int {
		global $wpdb;

		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$table  = aiwc_table( 'knowledge' );
		$format = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$format})", $ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Toggle the active status of an entry.
	 *
	 * @param int    $id     Row id.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function set_status( int $id, string $status ): bool {
		global $wpdb;

		$status = in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active';

		return (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'knowledge' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Count active knowledge entries.
	 *
	 * @return int
	 */
	public static function count(): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . aiwc_table( 'knowledge' ) . '` WHERE status = %s',
				'active'
			)
		);
	}

	/**
	 * Fetch knowledge entries, optionally filtered and paginated.
	 *
	 * @param array $args Options: page, per_page, post_type, status, search.
	 * @return array{items:array,total:int,pages:int}
	 */
	public static function list_all( array $args = array() ): array {
		global $wpdb;

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 200, (int) ( $args['per_page'] ?? 20 ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		$where    = array();
		$params   = array();

		if ( isset( $args['post_type'] ) && '' !== $args['post_type'] ) {
			$where[]  = 'post_type = %s';
			$params[] = $args['post_type'];
		}
		if ( isset( $args['status'] ) && '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( isset( $args['search'] ) && '' !== $args['search'] ) {
			$where[]  = '(title LIKE %s OR content LIKE %s)';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		$count_sql = "SELECT COUNT(*) FROM `" . aiwc_table( 'knowledge' ) . "` {$where_sql}";
		$list_sql  = "SELECT * FROM `" . aiwc_table( 'knowledge' ) . "` {$where_sql} ORDER BY updated_at DESC LIMIT %d OFFSET %d";

		$query_params = array_merge( $params, array( $per_page, $offset ) );

		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, $query_params ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB

		foreach ( $items as &$item ) {
			$item['content_length'] = mb_strlen( (string) $item['content'] );
		}

		return array(
			'items' => (array) $items,
			'total' => $total,
			'pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	/**
	 * Retrieve the most relevant knowledge for a query.
	 *
	 * @param string $query User question.
	 * @param int    $limit Maximum results to return.
	 * @return array<int,array{title:string,url:string,content:string,score:float,snippet:string}>
	 */
	public static function retrieve( string $query, int $limit = 0 ): array {
		if ( ! (bool) Settings::get_setting( 'knowledge.retrieval_enabled', true ) ) {
			return array();
		}

		if ( $limit <= 0 ) {
			$limit = (int) Settings::get_setting( 'knowledge.retrieval_count', 5 );
		}

		$results = self::search( $query, 50 );
		$results = array_slice( $results, 0, $limit );

		$filtered = apply_filters( 'ai_chatbot_retrieve_knowledge', $results, $query, $limit );

		return is_array( $filtered ) ? $filtered : $results;
	}

	/**
	 * Retrieve FAQs that match a query. FAQs are treated as high priority
	 * knowledge and are therefore searched separately.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Maximum results.
	 * @return array<int,array{title:string,url:string,content:string,score:float,type:string}>
	 */
	public static function retrieve_faqs( string $query, int $limit = 3 ): array {
		global $wpdb;

		$tokens = self::tokens( $query );
		if ( empty( $tokens ) ) {
			return array();
		}

		$table   = aiwc_table( 'faqs' );
		$like    = array();
		$params  = array();

		foreach ( $tokens as $token ) {
			$like[]   = '(question LIKE %s OR answer LIKE %s)';
			$pat      = '%' . $wpdb->esc_like( $token ) . '%';
			$params[] = $pat;
			$params[] = $pat;
		}

		$params[] = $limit;

		$sql = "SELECT id, question, answer FROM {$table}
			WHERE status = 'active' AND " . implode( ' OR ', $like ) . "
			ORDER BY sort_order ASC LIMIT %d";

		$rows    = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$results = array();

		foreach ( (array) $rows as $row ) {
			$score = self::score( $tokens, (string) $row['question'], 4 ) + self::score( $tokens, (string) $row['answer'], 1 );
			if ( $score <= 0 ) {
				continue;
			}

			$results[] = array(
				'source_id' => 'faq:' . (int) $row['id'],
				'title'     => (string) $row['question'],
				'url'       => '',
				'content'   => (string) $row['question'] . "\n" . (string) $row['answer'],
				'snippet'   => (string) $row['answer'],
				'score'     => $score,
				'type'      => 'faq',
			);
		}

		usort(
			$results,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return $results;
	}

	/**
	 * Keyword relevance search over knowledge entries. Uses OR matching with
	 * IDF-weighted, logarithmically saturated scoring so common words ("internet",
	 * "data") never outrank specific terms such as a provider name.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Maximum candidates.
	 * @return array
	 */
	public static function search( string $query, int $limit = 50 ): array {
		global $wpdb;

		$tokens = self::tokens( $query );
		if ( empty( $tokens ) ) {
			return array();
		}

		$table   = aiwc_table( 'knowledge' );
		$params  = array();
		$like    = array();
		$like_p  = array();

		foreach ( $tokens as $token ) {
			$like[]  = '(title LIKE %s OR content LIKE %s)';
			$pat     = '%' . $wpdb->esc_like( $token ) . '%';
			$like_p[] = $pat;
			$like_p[] = $pat;
		}

		$conds  = array( implode( ' OR ', $like ) );
		$params = array_merge( $like_p, array( $limit ) );

		$sql = "SELECT id, source_id, post_id, post_type, title, url, content FROM {$table}
			WHERE status = 'active' AND " . implode( ' AND ', $conds ) . "
			LIMIT %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB

		$total = self::count();
		$idf   = array();
		foreach ( $tokens as $token ) {
			$df = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND (title LIKE %s OR content LIKE %s)",
					'%' . $wpdb->esc_like( $token ) . '%',
					'%' . $wpdb->esc_like( $token ) . '%'
				)
			);
			$idf[ $token ] = $df > 0 ? log( ( $total + 1 ) / ( $df + 1 ) ) + 1.0 : 2.5;
		}

		$results = array();
		foreach ( (array) $rows as $row ) {
			$title_score   = self::score( $tokens, (string) $row['title'], 6, $idf );
			$content_score = self::score( $tokens, (string) $row['content'], 2, $idf );
			$total_score   = $title_score + $content_score;
			if ( $total_score <= 0 ) {
				continue;
			}

			$snippet = self::snippet( (string) $row['content'], $tokens );

			$results[] = array(
				'source_id' => (string) $row['source_id'],
				'post_id'   => (int) $row['post_id'],
				'post_type' => (string) $row['post_type'],
				'title'     => (string) $row['title'],
				'url'       => (string) $row['url'],
				'snippet'   => $snippet,
				'content'   => (string) $row['content'],
				'score'     => $total_score,
			);
		}

		usort(
			$results,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return $results;
	}

	/**
	 * Tokenise a query for keyword search.
	 *
	 * @param string $text Query.
	 * @return array<string>
	 */
	private static function tokens( string $text ): array {
		$stop = array( 'i', 'me', 'my', 'we', 'our', 'you', 'your', 'a', 'an', 'the', 'is', 'are', 'am', 'was', 'were', 'be', 'been', 'being', 'has', 'have', 'had', 'do', 'does', 'did', 'what', 'which', 'who', 'whom', 'whose', 'how', 'where', 'when', 'why', 'of', 'in', 'on', 'for', 'to', 'at', 'and', 'or', 'not', 'no', 'can', 'could', 'would', 'will', 'shall', 'should', 'may', 'might', 'must', 'please', 'tell', 'about', 'with', 'without', 'from', 'by', 'than', 'then', 'this', 'that', 'these', 'those', 'there', 'here', 'it', 'its', 'them', 'they', 'so', 'as', 'if', 'but', 'also', 'too', 'very', 'just', 'get', 'got', 'many', 'much', 'more', 'most', 'some', 'any', 'all', 'into', 'out', 'up', 'down', 'over', 'under', 'off', 'list', 'help', 'want', 'need', 'like', 'know', 'say', 'us', 'time', 'day', 'way', 'things' );

		$words = preg_split( '/[^a-z0-9]+/i', mb_strtolower( $text ) );
		$words = array_filter(
			(array) $words,
			static function ( $word ) use ( $stop ) {
				$word = trim( (string) $word );

				return mb_strlen( $word ) > 2 && ! in_array( $word, $stop, true );
			}
		);

		return array_values( array_unique( $words ) );
	}

	/**
	 * Score a document for a set of tokens. The recurring count is logarithmically
	 * saturated and each token is weighted by its inverse document frequency so
	 * rare, specific terms dominate over common words repeated in every entry.
	 *
	 * @param array  $tokens Tokens.
	 * @param string $text   Document text.
	 * @param float  $weight Weight multiplier per match.
	 * @param array  $idf    Inverse document frequency per token.
	 * @return float
	 */
	private static function score( array $tokens, string $text, float $weight, array $idf = array() ): float {
		$lower = mb_strtolower( $text );
		$score = 0.0;

		foreach ( $tokens as $token ) {
			$count = substr_count( $lower, $token );
			if ( $count <= 0 ) {
				continue;
			}

			$score += $weight * ( $idf[ $token ] ?? 1.0 ) * log1p( $count );
		}

		return $score;
	}

	/**
	 * Build a short snippet around the first token match.
	 *
	 * @param string $content Content.
	 * @param array  $tokens  Query tokens.
	 * @return string
	 */
	private static function snippet( string $content, array $tokens ): string {
		$lower = mb_strtolower( $content );
		$pos   = -1;

		foreach ( $tokens as $token ) {
			$found = mb_strpos( $lower, $token );
			if ( false !== $found && ( $pos < 0 || $found < $pos ) ) {
				$pos = $found;
			}
		}

		if ( $pos < 0 ) {
			return mb_substr( $content, 0, 180 );
		}

		$start = max( 0, $pos - 40 );

		return '…' . mb_substr( $content, $start, 180 ) . '…';
	}
}