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
	 * Build a text block from post meta (custom fields) by flattening values,
	 * skipping internal keys, ACF field refs, serialized blobs, images and
	 * other noise.  Works generically across any post type: each key-value
	 * pair is emitted as "key: value" so plans, prices, specs, testimonials
	 * and FaQ sections all contribute to search.
	 *
	 * @param int $post_id     Post ID.
	 * @param int $max_items   Maximum leaf values to include overall.
	 * @param int $max_per_key Cap per key so a single huge repeatable field
	 *                         cannot crowd out other keys (default 40).
	 * @return string
	 */
	public static function meta_content( int $post_id, int $max_items = 300, int $max_per_key = 40 ): string {
		$meta  = get_post_meta( $post_id );
		$parts = array();

		foreach ( (array) $meta as $key => $values ) {
			$key = (string) $key;
			if ( '' === $key || self::is_internal_meta_key( $key ) ) {
				continue;
			}

			$per_key = 0;
			foreach ( (array) $values as $value ) {
				foreach ( self::flatten_meta_value( $value ) as $text ) {
					$text = aiwc_normalize_content( (string) $text );
					if ( '' !== $text && ! self::is_noise_value( $text ) ) {
						$parts[] = $key . ': ' . $text;
						if ( ++$per_key >= $max_per_key ) {
							break 2;
						}
					}
					if ( count( $parts ) >= $max_items ) {
						break 3;
					}
				}
			}
		}

		return implode( "\n", $parts );
	}

	/**
	 * Recursively flatten a meta value into an array of leaf strings.
	 * Handles nested arrays, PHP serialized data and JSON so any plugin's
	 * storage format (ACF, CMB2, WooCommerce, Elementor, custom) can be
	 * indexed.
	 *
	 * @param mixed $value Value from get_post_meta.
	 * @param int   $depth Recursion depth guard.
	 * @return string[]
	 */
	private static function flatten_meta_value( $value, int $depth = 0 ): array {
		if ( is_array( $value ) ) {
			if ( $depth > 3 || count( $value ) > 100 ) {
				return array();
			}
			$out = array();
			foreach ( $value as $v ) {
				foreach ( self::flatten_meta_value( $v, $depth + 1 ) as $t ) {
					$out[] = $t;
				}
			}
			return $out;
		}

		if ( ! is_scalar( $value ) ) {
			return array();
		}

		$s = (string) $value;

		if ( is_serialized( $s ) ) {
			$u = @unserialize( $s, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_function_unserialize
			if ( is_array( $u ) ) {
				if ( count( $u ) > 100 ) {
					return array();
				}
				return self::flatten_meta_value( $u, $depth + 1 );
			}
			if ( is_scalar( $u ) && is_serialized( (string) $u ) ) {
				return self::flatten_meta_value( $u, $depth + 1 );
			}
			return array();
		}

		if ( strlen( $s ) > 2 ) {
			$json = json_decode( $s, true );
			if ( is_array( $json ) && count( $json ) <= 100 ) {
				return self::flatten_meta_value( $json, $depth + 1 );
			}
		}

		return array( $s );
	}

	/**
	 * Whether a meta key is internal bookkeeping rather than authored
	 * content.  Leading-underscore keys are WordPress conventions; the
	 * extra tokens catch well-known non-underscored keys.
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	private static function is_internal_meta_key( string $key ): bool {
		if ( 0 === strpos( $key, '_' ) ) {
			return true;
		}

		$lkey = strtolower( $key );
		if ( preg_match( '/^(edit_lock|edit_last|wp_page_template|last_editor_used_jetpack|rank_math|yoast)/', $lkey ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine whether a normalized leaf value is junk (image URLs, field
	 * refs, plain IDs, hashes, asset paths etc.) and should not be indexed.
	 *
	 * @param string $text Normalized text.
	 * @return bool
	 */
	private static function is_noise_value( string $text ): bool {
		if ( '' === $text || ctype_space( $text ) ) {
			return true;
		}
		if ( mb_strlen( $text ) < 2 ) {
			return true;
		}
		if ( preg_match( '/^field_[a-f0-9]{13}$/i', $text ) ) {
			return true;
		}
		if ( filter_var( $text, FILTER_VALIDATE_URL ) ) {
			return true;
		}
		if ( false !== stripos( $text, 'wp-content' ) ) {
			return true;
		}
		if ( preg_match( '/^[a-f0-9]{32}$/i', $text ) || preg_match( '/^[a-f0-9]{40}$/i', $text ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether custom fields (post meta) should be included for a given post
	 * type. Requires the global master switch to be on; a per-type allowlist
	 * ("meta_types") can then restrict which post types contribute meta.
	 *
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function meta_enabled_for( string $post_type ): bool {
		if ( ! (bool) Settings::get_setting( 'knowledge.index_custom_fields', true ) ) {
			return false;
		}

		$meta_types = array_values(
			array_filter(
				array_map( 'sanitize_key', (array) Settings::get_setting( 'knowledge.meta_types', array() ) )
			)
		);

		return empty( $meta_types ) || in_array( $post_type, $meta_types, true );
	}

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

		if ( self::meta_enabled_for( $post->post_type ) ) {
			$meta = self::meta_content( $post_id );
			if ( '' !== $meta ) {
				$content .= "\n" . $meta;
			}
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
	 * Maintain one "overview" knowledge entry per selected post type that
	 * lists the published entries so the assistant can answer site-level
	 * questions such as "how many providers do you have?".
	 *
	 * @return void
	 */
	public static function index_type_overviews(): void {
		global $wpdb;

		foreach ( self::indexable_post_types() as $type ) {
			$object = get_post_type_object( $type );
			if ( ! $object ) {
				continue;
			}

			$label = (string) $object->labels->name;
			$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_title FROM {$wpdb->posts}
					WHERE post_status = 'publish' AND post_type = '%s'
					AND (post_password = '' OR post_password IS NULL)
					ORDER BY post_title ASC",
					$type
				)
			);

			$names = array();
			foreach ( (array) $rows as $row ) {
				$title = trim( (string) $row->post_title );
				if ( '' !== $title ) {
					$names[] = $title;
				}
			}

			if ( empty( $names ) ) {
				Knowledge_Base::delete_source( 'type:' . $type );
				continue;
			}

			$content = wp_sprintf(
				/* translators: 1: entry count, 2: post type label, 3: list of entry names. */
				__( 'The website currently lists %1$d %2$s: %3$s.', 'ai-website-chatbot' ),
				count( $names ),
				$label,
				implode( ', ', $names )
			);

			Knowledge_Base::upsert(
				array(
					'source_id'    => 'type:' . $type,
					'post_id'      => 0,
					'post_type'    => $type,
					'title'        => $label,
					'url'          => (string) get_post_type_archive_link( $type ),
					'content'      => $content,
					'content_hash' => hash( 'sha256', $content ),
					'metadata'     => array( 'overview' => true, 'post_type' => $type, 'count' => count( $names ) ),
				)
			);
		}
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
			self::index_type_overviews();
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
		self::index_type_overviews();

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

			if ( 0 === $post_id && 0 === strpos( (string) $row['source_id'], 'type:' ) ) {
				$overview_type = substr( (string) $row['source_id'], 5 );
				if ( ! in_array( $overview_type, self::indexable_post_types(), true ) ) {
					Knowledge_Base::delete( (int) $row['id'] );
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
		self::index_type_overviews();
	}
}