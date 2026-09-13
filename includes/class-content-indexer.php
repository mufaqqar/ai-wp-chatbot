<?php
/**
 * Content indexing engine.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Indexes pages, posts, custom post types, WooCommerce products and FAQs into
 * the knowledge table. Content is only re-indexed when its hash changes.
 */
class Content_Indexer {

	public const PROGRESS_OPTION = 'aiwc_index_progress';

	/**
	 * Post types excluded from the "Content to index" selector because they are
	 * internal system types (media, revisions, template parts, orders etc.).
	 *
	 * @var array
	 */
	private const EXCLUDED_TYPES = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_navigation',
		'wp_global_styles',
		'wp_font_library',
		'wp_font_family',
		'wp_font_face',
		'product_variation',
		'shop_order',
		'shop_order_refund',
		'shop_coupon',
		'shop_webhook',
	);

	/**
	 * Post types currently selected for indexing (pages, posts and any enabled
	 * custom post types from the Knowledge Base screen). The selection is the
	 * single source of truth — WooCommerce products are indexed too when their
	 * post type is selected.
	 *
	 * @return array
	 */
	public static function indexable_post_types(): array {
		return array_values(
			array_filter(
				array_unique(
					array_map(
						'sanitize_key',
						(array) Settings::get_setting( 'knowledge.content_types', array( 'page', 'post' ) )
					)
				),
				'post_type_exists'
			)
		);
	}

	/**
	 * Post types available for the "Content to index" selector. Public post
	 * types with internal/system types filtered out.
	 *
	 * @return array<string,\WP_Post_Type>
	 */
	public static function available_post_types(): array {
		$types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $name => $object ) {
			if ( in_array( $name, self::EXCLUDED_TYPES, true ) ) {
				continue;
			}
			$types[ $name ] = $object;
		}

		return $types;
	}

	/**
	 * Number of publishable candidate posts per available post type.
	 *
	 * @return array<string,int>
	 */
	public static function candidate_counts_by_type(): array {
		global $wpdb;

		$available = self::available_post_types();
		if ( empty( $available ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $available ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_type, COUNT(*) AS c FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_type IN ({$placeholders})
				GROUP BY post_type",
				array_keys( $available )
			),
			ARRAY_A
		);
		// phpcs:enable

		$counts = array();
		foreach ( (array) $results as $row ) {
			$counts[ $row['post_type'] ] = (int) $row['c'];
		}

		return $counts;
	}

	/**
	 * Whether a single post can be indexed.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public static function is_indexable( \WP_Post $post ): bool {
		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! empty( $post->post_password ) ) {
			return false;
		}

		if ( '' === trim( (string) $post->post_content ) && '' === trim( (string) get_the_excerpt( $post ) ) ) {
			$title = trim( (string) $post->post_title );
			if ( '' === $title ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Build a knowledge row for a post.
	 *
	 * @param int $post_id Post id.
	 * @return array|null
	 */
	public static function build_post_row( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || ! self::is_indexable( $post ) ) {
			return null;
		}

		$body = aiwc_normalize_content( (string) $post->post_content );
		$exec = trim( (string) get_the_excerpt( $post ) );
		$title = trim( (string) $post->post_title );

		$content = $title;
		if ( '' !== $exec ) {
			$content .= "\n" . $exec;
		}
		if ( '' !== $body ) {
			$content .= "\n" . $body;
		}

		$content = trim( $content );
		if ( '' === $content ) {
			return null;
		}

		return array(
			'source_id'    => 'post:' . $post_id . ':' . $post->post_type,
			'post_id'      => $post_id,
			'post_type'    => (string) $post->post_type,
			'title'        => $title,
			'url'          => get_permalink( $post ),
			'content'      => $content,
			'content_hash' => hash( 'sha256', $content ),
			'metadata'     => array( 'author' => (int) $post->post_author, 'created' => $post->post_date ),
		);
	}

	/**
	 * Index a single post. Skips when the content hash is unchanged.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public static function index_post( int $post_id ): bool {
		$row = self::build_post_row( $post_id );
		if ( ! $row ) {
			self::unindex_post( $post_id );
			return false;
		}

		$existing = Knowledge_Base::get_by_post( $post_id, $row['post_type'] );
		if ( $existing && hash_equals( (string) $existing['content_hash'], $row['content_hash'] ) ) {
			return true;
		}

		Knowledge_Base::upsert( $row );

		if ( aiwc_is_woocommerce_active() && 'product' === $row['post_type'] ) {
			Logger::info( 'index', 'Product indexed', array( 'post_id' => $post_id ) );
		}

		return true;
	}

	/**
	 * Remove a post from the knowledge base.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function unindex_post( int $post_id ): void {
		Knowledge_Base::delete_by_post( $post_id );
	}

	/**
	 * Index all active FAQs as knowledge entries.
	 *
	 * @return int Number of FAQs indexed.
	 */
	public static function index_faqs(): int {
		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT id, question, answer, category FROM `' . aiwc_table( 'faqs' ) . '` WHERE status = %s ORDER BY sort_order ASC, id ASC',
				'active'
			),
			ARRAY_A
		);

		$count = 0;
		foreach ( (array) $rows as $row ) {
			$content = (string) $row['question'] . "\n" . (string) $row['answer'];

			Knowledge_Base::upsert(
				array(
					'source_id'    => 'faq:' . (int) $row['id'],
					'post_id'      => 0,
					'post_type'    => 'faq',
					'title'        => (string) $row['question'],
					'url'          => '',
					'content'      => $content,
					'content_hash' => hash( 'sha256', $content ),
					'metadata'     => array( 'faq_id' => (int) $row['id'], 'category' => (string) $row['category'] ),
				)
			);
			++$count;
		}

		return $count;
	}

	/**
	 * Run a full re-index in batches, reporting progress via an option so the
	 * admin UI can walk through large sites without blocking.
	 *
	 * @param int $batch_size Number of items per call.
	 * @return array{batch:int,total:int,remaining:int,done:int,next_offset:int,failed:int,finished:bool}
	 */
	public static function index_all( int $batch_size = 50 ): array {
		global $wpdb;

		$progress = (array) get_option( self::PROGRESS_OPTION, array() );
		$offset   = (int) ( $progress['offset'] ?? 0 );
		$failed   = (int) ( $progress['failed'] ?? 0 );

		if ( ! isset( $progress['total'] ) ) {
			$progress['total']  = self::total_candidates();
			$progress['done']   = 0;
			$progress['offset'] = 0;
			$progress['failed'] = 0;
			$offset             = 0;
		}

		$types = self::indexable_post_types();

		if ( empty( $types ) || (int) $progress['total'] <= 0 ) {
			self::index_faqs();
			self::purge_stale();
			delete_option( self::PROGRESS_OPTION );

			return array(
				'batch'       => 0,
				'total'       => 0,
				'done'        => 0,
				'remaining'   => 0,
				'next_offset' => 0,
				'failed'      => $failed,
				'finished'    => true,
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"SELECT ID, post_type FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type IN ({$placeholders})
			AND (post_password = '' OR post_password IS NULL)
			ORDER BY ID ASC LIMIT %d OFFSET %d",
			array_merge( $types, array( $batch_size, $offset ) )
		);
		// phpcs:enable

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$done = 0;
		foreach ( (array) $rows as $row ) {
			try {
				self::index_post( (int) $row['ID'] );
			} catch ( \Throwable $e ) {
				++$failed;
				Logger::error( 'indexing', 'Failed to index post', array( 'post' => (int) $row['ID'], 'message' => $e->getMessage() ) );
			}
			++$done;
		}

		$offset      += $done;
		$total_done   = (int) ( $progress['done'] ?? 0 ) + $done;
		$total        = (int) ( $progress['total'] ?? 0 );
		$finished     = $done < $batch_size || $offset >= $total;

		self::index_faqs();

		if ( $finished ) {
			self::purge_stale();
			delete_option( self::PROGRESS_OPTION );

			return array(
				'batch'     => $done,
				'total'     => $total,
				'done'      => $total_done,
				'remaining' => 0,
				'next_offset' => $offset,
				'failed'    => $failed,
				'finished'  => true,
			);
		}

		update_option( self::PROGRESS_OPTION, array( 'total' => $total, 'done' => $total_done, 'offset' => $offset, 'failed' => $failed ) );

		return array(
			'batch'     => $done,
			'total'     => $total,
			'done'      => $total_done,
			'remaining' => max( 0, $total - $total_done ),
			'next_offset' => $offset,
			'failed'    => $failed,
			'finished'  => false,
		);
	}

	/**
	 * Count candidate posts for indexing.
	 *
	 * @return int
	 */
	public static function total_candidates(): int {
		global $wpdb;

		$types = self::indexable_post_types();
		if ( empty( $types ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_type IN ({$placeholders})",
				$types
			)
		);
		// phpcs:enable
	}

	/**
	 * Remove knowledge rows whose source post or FAQ no longer exists.
	 *
	 * @return void
	 */
	public static function purge_stale(): void {
		global $wpdb;

		$table   = aiwc_table( 'knowledge' );
		$rows    = $wpdb->get_results( "SELECT id, source_id, post_id, post_type FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		foreach ( (array) $rows as $row ) {
			$post_id = (int) $row['post_id'];
			$type    = (string) $row['post_type'];

			if ( 'faq' === $type ) {
				$faq_id = (int) str_replace( 'faq:', '', (string) $row['source_id'] );
				if ( $faq_id > 0 ) {
					$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM `' . aiwc_table( 'faqs' ) . '` WHERE id = %d LIMIT 1', $faq_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					if ( ! $exists ) {
						Knowledge_Base::delete( (int) $row['id'] );
					}
				}
				continue;
			}

			if ( $post_id > 0 ) {
				$exists = get_post_status( $post_id );
				if ( false === $exists || 'publish' !== $exists ) {
					Knowledge_Base::delete( (int) $row['id'] );
				}
			}
		}
	}

	/**
	 * Reset index progress so a fresh indexing run starts clean.
	 *
	 * @return void
	 */
	public static function reset_progress(): void {
		delete_option( self::PROGRESS_OPTION );
	}

	/**
	 * Hook a single post for later indexing when content changes.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function mark_dirty( int $post_id ): void {
		set_transient( 'aiwc_dirty_post_' . $post_id, 1, DAY_IN_SECONDS );
	}

	/**
	 * Register content lifecycle hooks.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 10, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete_post' ) );
		add_action( 'aiwc_maintenance', array( __CLASS__, 'process_dirty_posts' ) );
	}

	/**
	 * Trigger re-indexing when content is saved.
	 *
	 * @param int     $post_id Post id.
	 * @param \WP_Post $post   Post.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	public static function on_save_post( int $post_id, \WP_Post $post, bool $update ): void {
		if ( (bool) Settings::get_setting( 'knowledge.auto_index', true ) ) {
			self::mark_dirty( $post_id );
		}
	}

	/**
	 * Remove knowledge entries when a post is deleted.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function on_delete_post( int $post_id ): void {
		self::unindex_post( $post_id );
	}

	/**
	 * Process all dirty posts in batches during maintenance cron.
	 *
	 * @return void
	 */
	public static function process_dirty_posts(): void {
		global $wpdb;

		$types = self::indexable_post_types();
		if ( empty( $types ) ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type IN ({$placeholders})
			AND ID IN (SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'aiwc_dirty_post_%')
			LIMIT 50",
			$types
		);
		// phpcs:enable

		$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach ( (array) $ids as $id ) {
			self::index_post( (int) $id );
			delete_transient( 'aiwc_dirty_post_' . (int) $id );
		}

		self::index_faqs();
	}
}