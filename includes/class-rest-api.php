<?php
/**
 * REST API registration and handlers.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Registers public and admin REST endpoints under the ai-chatbot/vi namespace.
 */
class REST_API {

	public const NAMESPACE = 'ai-chatbot/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/config',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_config' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/chat',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'chat' ),
				'permission_callback' => array( $this, 'public_nonce_check' ),
				'args'                => array(
					'message'         => array( 'type' => 'string', 'required' => true ),
					'conversation_id' => array( 'type' => 'string' ),
					'session_id'      => array( 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/lead',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'lead' ),
				'permission_callback' => array( $this, 'public_nonce_check' ),
				'args'                => array(
					'name'            => array( 'type' => 'string', 'required' => true ),
					'email'           => array( 'type' => 'string', 'required' => true ),
					'phone'           => array( 'type' => 'string' ),
					'message'         => array( 'type' => 'string' ),
					'conversation_id' => array( 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/feedback',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'feedback' ),
				'permission_callback' => array( $this, 'public_nonce_check' ),
				'args'                => array(
					'conversation_id' => array( 'type' => 'string', 'required' => true ),
					'message_id'      => array( 'type' => 'integer' ),
					'rating'          => array( 'type' => 'string', 'required' => true ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health' ),
				'permission_callback' => '__return_true',
			)
		);

		$this->register_admin_routes();
	}

	/**
	 * Register admin-only routes.
	 *
	 * @return void
	 */
	private function register_admin_routes(): void {
		$can_manage = static function () {
			return current_user_can( 'manage_options' );
		};

		register_rest_route( self::NAMESPACE, '/admin/stats', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_stats' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/config', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_config' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/settings', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_save_settings' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/api-key', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_save_api_key' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/test', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_test' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/index/start', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_index_start' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/index/step', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_index_step' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/knowledge', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_knowledge' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/knowledge/(?P<id>\d+)/toggle', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_knowledge_toggle' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/knowledge/(?P<id>\d+)/delete', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_knowledge_delete' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/faqs', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_faqs' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/faqs/create', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_faq_create' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/faqs/(?P<id>\d+)', array( 'methods' => \WP_REST_Server::EDITABLE, 'callback' => array( $this, 'admin_faq_update' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/faqs/(?P<id>\d+)/delete', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_faq_delete' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/leads', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_leads' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/leads/(?P<id>\d+)/status', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_lead_status' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/leads/(?P<id>\d+)/delete', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_lead_delete' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/conversations', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_conversations' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/conversations/(?P<id>\d+)/delete', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_conversation_delete' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/logs', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_logs' ), 'permission_callback' => $can_manage ) );
		register_rest_route( self::NAMESPACE, '/admin/logs/clear', array( 'methods' => \WP_REST_Server::CREATABLE, 'callback' => array( $this, 'admin_logs_clear' ), 'permission_callback' => $can_manage ) );

		register_rest_route( self::NAMESPACE, '/admin/health', array( 'methods' => \WP_REST_Server::READABLE, 'callback' => array( $this, 'admin_health' ), 'permission_callback' => $can_manage ) );
	}

	/**
	 * Verify the public chat nonce sent in the X-AIWC-Nonce header.
	 *
	 * The widget always sends a guest-scoped nonce, verified against the
	 * anonymous user context. WordPress REST requests without an X-WP-Nonce
	 * are treated as anonymous even when the browser is logged in, so the
	 * check must not rely on the current user.
	 *
	 * @return bool
	 */
	public function public_nonce_check(): bool {
		$nonce = isset( $_SERVER['HTTP_X_AIWC_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_AIWC_NONCE'] ) ) : '';

		return Security::verify_nonce( 0, $nonce );
	}

	/**
	 * GET /config — sanitized widget configuration.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_config() {
		$handoff = Settings::get_setting( 'handoff', array() );

		$config = array(
			'version'           => AIWC_VERSION,
			'enabled'           => (bool) Settings::get_setting( 'general.enabled', true ),
			'bot_name'          => (string) Settings::get_setting( 'general.bot_name', 'AI Assistant' ),
			'welcome_message'   => (string) Settings::get_setting( 'general.welcome_message', '' ),
			'lead_prompt'       => (string) Settings::get_setting( 'general.lead_prompt', '' ),
			'collect_leads'     => (bool) Settings::get_setting( 'general.collect_leads', true ),
			'show_sources'      => (bool) Settings::get_setting( 'knowledge.show_sources', true ),
			'position'          => (string) Settings::get_setting( 'appearance.position', 'bottom-right' ),
			'primary_color'     => (string) Settings::get_setting( 'appearance.primary_color', '#2563eb' ),
			'button_color'      => (string) Settings::get_setting( 'appearance.button_color', '#2563eb' ),
			'text_color'        => (string) Settings::get_setting( 'appearance.text_color', '#ffffff' ),
			'width'             => (int) Settings::get_setting( 'appearance.width', 380 ),
			'height'            => (int) Settings::get_setting( 'appearance.height', 560 ),
			'border_radius'     => (int) Settings::get_setting( 'appearance.border_radius', 12 ),
			'button_icon'       => (string) Settings::get_setting( 'appearance.button_icon', 'default' ),
			'custom_css'        => (string) Settings::get_setting( 'appearance.custom_css', '' ),
			'default_language'  => (string) Settings::get_setting( 'general.default_language', 'en' ),
			'handoff'           => array(
				'enabled'              => (bool) ( $handoff['enabled'] ?? true ),
				'support_phone'        => (string) ( $handoff['support_phone'] ?? '' ),
				'support_email'        => (string) ( $handoff['support_email'] ?? '' ),
				'whatsapp_link'        => (string) ( $handoff['whatsapp_link'] ?? '' ),
				'contact_form_enabled' => (bool) ( $handoff['contact_form_enabled'] ?? true ),
			),
			'api'               => array(
				'chat'     => esc_url_raw( rest_url( self::NAMESPACE . '/chat' ) ),
				'lead'     => esc_url_raw( rest_url( self::NAMESPACE . '/lead' ) ),
				'feedback' => esc_url_raw( rest_url( self::NAMESPACE . '/feedback' ) ),
				'nonce'    => Security::create_nonce(),
			),
		);

		/**
		 * Filter the configuration exposed to the widget.
		 *
		 * @param array $config Config.
		 */
		$config = apply_filters( 'ai_chatbot_widget_config', $config );

		return rest_ensure_response( $config );
	}

	/**
	 * POST /chat
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function chat( \WP_REST_Request $request ) {
		$payload = $this->request_params( $request, array( 'message', 'conversation_id', 'session_id', 'name', 'email', 'phone', 'lead_message', 'history' ) );

		$result = Chatbot::process( $payload, (int) $this->target_user_id() );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /lead
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function lead( \WP_REST_Request $request ) {
		$limits = Security::check_limits( 'lead' );
		if ( is_wp_error( $limits ) ) {
			return $this->error_response( $limits );
		}

		$lead_id = Leads::add(
			array(
				'name'            => (string) $request->get_param( 'name' ),
				'email'           => (string) $request->get_param( 'email' ),
				'phone'           => (string) $request->get_param( 'phone' ),
				'message'         => (string) $request->get_param( 'message' ),
				'conversation_id' => (string) $request->get_param( 'conversation_id' ),
				'source'          => 'chatbot',
			)
		);

		if ( is_wp_error( $lead_id ) ) {
			return $this->error_response( $lead_id );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'lead_id' => $lead_id,
			)
		);
	}

	/**
	 * POST /feedback
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function feedback( \WP_REST_Request $request ) {
		global $wpdb;

		$conversation_id = substr( sanitize_text_field( (string) $request->get_param( 'conversation_id' ) ), 0, 32 );
		$message_id      = (int) $request->get_param( 'message_id' );
		$rating          = strtolower( sanitize_key( (string) $request->get_param( 'rating' ) ) );
		$value           = in_array( $rating, array( 'positive', 'up', 'thumbs_up', 'good' ), true ) ? 1 : ( in_array( $rating, array( 'negative', 'down', 'thumbs_down', 'bad' ), true ) ? -1 : 0 );

		if ( $message_id > 0 ) {
			$wpdb->update( aiwc_table( 'messages' ), array( 'feedback' => $value ), array( 'id' => $message_id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->update( aiwc_table( 'messages' ), array( 'feedback' => $value ), array( 'conversation_id' => $conversation_id ), array( '%d' ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		Logger::info( 'feedback', 'Feedback recorded', array( 'conversation' => $conversation_id, 'rating' => $value ) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * GET /health — minimal public health.
	 *
	 * @return \WP_REST_Response
	 */
	public function health() {
		if ( current_user_can( 'manage_options' ) ) {
			return $this->admin_health();
		}

		return rest_ensure_response(
			array(
				'status'  => 'ok',
				'version' => AIWC_VERSION,
			)
		);
	}

	/**
	 * GET /admin/stats
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_stats() {
		return rest_ensure_response( Analytics::stats() );
	}

	/**
	 * GET /admin/config — safe settings needed by admin JS.
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_config() {
		return rest_ensure_response(
			array(
				'settings'  => Settings::get(),
				'has_api_key' => '' !== Settings::get_api_key(),
				'api_key_masked' => self::mask( Settings::get_api_key() ),
				'tables'    => Database::existing_tables(),
				'version'   => AIWC_VERSION,
				'db_version' => get_option( 'aiwc_db_version' ),
				'woocommerce_active' => aiwc_is_woocommerce_active(),
			)
		);
	}

	/**
	 * POST /admin/settings
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_save_settings( \WP_REST_Request $request ) {
		$raw     = $request->get_json_params();
		$cleaned = Settings::sanitize( is_array( $raw ) ? $raw : array() );
		Settings::update( $cleaned );

		return rest_ensure_response( array( 'success' => true, 'settings' => Settings::get() ) );
	}

	/**
	 * POST /admin/api-key
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_save_api_key( \WP_REST_Request $request ) {
		$key      = trim( (string) $request->get_param( 'api_key' ) );
		$provider = sanitize_text_field( (string) $request->get_param( 'provider' ) );
		Settings::set_api_key( $key, '' !== $provider ? $provider : '' );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * POST /admin/test
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_test( \WP_REST_Request $request ) {
		$result = Chatbot::test( array( 'message' => (string) $request->get_param( 'message' ) ) );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result );
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /admin/index/start
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_index_start() {
		Content_Indexer::reset_progress();

		return rest_ensure_response( Content_Indexer::index_all( 50 ) );
	}

	/**
	 * GET /admin/index/step — run one indexing batch.
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_index_step() {
		return rest_ensure_response( Content_Indexer::index_all( 50 ) );
	}

	/**
	 * GET /admin/knowledge
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_knowledge( \WP_REST_Request $request ) {
		return rest_ensure_response(
			Knowledge_Base::list_all(
				array(
					'page'      => (int) $request->get_param( 'page' ),
					'per_page'  => (int) $request->get_param( 'per_page' ),
					'post_type' => sanitize_text_field( (string) $request->get_param( 'post_type' ) ),
					'status'    => sanitize_text_field( (string) $request->get_param( 'status' ) ),
					'search'    => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				)
			)
		);
	}

	/**
	 * POST /admin/knowledge/{id}/toggle
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_knowledge_toggle( \WP_REST_Request $request ) {
		$status = ( 'inactive' === $request->get_param( 'status' ) ) ? 'inactive' : 'active';

		return rest_ensure_response( array( 'success' => Knowledge_Base::set_status( (int) $request['id'], $status ) ) );
	}

	/**
	 * POST /admin/knowledge/{id}/delete
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_knowledge_delete( \WP_REST_Request $request ) {
		Knowledge_Base::delete( (int) $request['id'] );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * GET /admin/faqs
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_faqs( \WP_REST_Request $request ) {
		global $wpdb;

		$per_page = max( 1, min( 200, (int) $request->get_param( 'per_page' ) ) );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = aiwc_table( 'faqs' );

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY sort_order ASC, id DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB

		return rest_ensure_response(
			array(
				'items' => (array) $items,
				'total' => $total,
				'pages' => max( 1, (int) ceil( $total / $per_page ) ),
			)
		);
	}

	/**
	 * POST /admin/faqs/create
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_faq_create( \WP_REST_Request $request ) {
		global $wpdb;

		$faq = $this->sanitize_faq( $request );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			aiwc_table( 'faqs' ),
			array_merge(
				$faq,
				array(
					'sort_order' => max( 0, (int) $request->get_param( 'sort_order' ) ),
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				)
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		$id = (int) $wpdb->insert_id;
		if ( $id > 0 ) {
			Content_Indexer::index_faqs();
		}

		return rest_ensure_response( array( 'success' => true, 'id' => $id ) );
	}

	/**
	 * POST /admin/faqs/{id}
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_faq_update( \WP_REST_Request $request ) {
		global $wpdb;

		$faq = $this->sanitize_faq( $request );

		$wpdb->update( aiwc_table( 'faqs' ), array_merge( $faq, array( 'sort_order' => max( 0, (int) $request->get_param( 'sort_order' ) ), 'updated_at' => current_time( 'mysql' ) ) ), array( 'id' => (int) $request['id'] ), array( '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		Content_Indexer::index_faqs();

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * POST /admin/faqs/{id}/delete
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_faq_delete( \WP_REST_Request $request ) {
		global $wpdb;

		$wpdb->delete( aiwc_table( 'faqs' ), array( 'id' => (int) $request['id'] ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Knowledge_Base::delete_by_post( 0 ); // no-op safety
		Content_Indexer::index_faqs();

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Sanitize FAQ fields from a request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array
	 */
	private function sanitize_faq( \WP_REST_Request $request ): array {
		return array(
			'question' => sanitize_textarea_field( (string) $request->get_param( 'question' ) ),
			'answer'   => sanitize_textarea_field( (string) $request->get_param( 'answer' ) ),
			'category' => sanitize_text_field( (string) $request->get_param( 'category' ) ),
			'status'   => ( 'inactive' === $request->get_param( 'status' ) ) ? 'inactive' : 'active',
		);
	}

	/**
	 * GET /admin/leads
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_leads( \WP_REST_Request $request ) {
		return rest_ensure_response(
			Leads::list_all(
				array(
					'page'     => (int) $request->get_param( 'page' ),
					'per_page' => (int) $request->get_param( 'per_page' ),
					'status'   => sanitize_text_field( (string) $request->get_param( 'status' ) ),
					'search'   => sanitize_text_field( (string) $request->get_param( 'search' ) ),
				)
			)
		);
	}

	/**
	 * POST /admin/leads/{id}/status
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_lead_status( \WP_REST_Request $request ) {
		$success = Leads::update_status( (int) $request['id'], sanitize_key( (string) $request->get_param( 'status' ) ) );

		return rest_ensure_response( array( 'success' => $success ) );
	}

	/**
	 * POST /admin/leads/{id}/delete
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_lead_delete( \WP_REST_Request $request ) {
		Leads::delete( (int) $request['id'] );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * GET /admin/conversations
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_conversations( \WP_REST_Request $request ) {
		global $wpdb;

		$data = Conversations::list_all(
			(int) $request->get_param( 'page' ),
			(int) $request->get_param( 'per_page' ),
			sanitize_text_field( (string) $request->get_param( 'search' ) )
		);

		foreach ( $data['items'] as &$conversation ) {
			$conversation['messages'] = $wpdb->get_results( $wpdb->prepare( 'SELECT id, role, message, created_at FROM `' . aiwc_table( 'messages' ) . '` WHERE conversation_id = %s ORDER BY id ASC LIMIT 50', $conversation['conversation_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		return rest_ensure_response( $data );
	}

	/**
	 * POST /admin/conversations/{id}/delete
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function admin_conversation_delete( \WP_REST_Request $request ) {
		Conversations::delete( (int) $request['id'] );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * GET /admin/logs
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_logs() {
		return rest_ensure_response( array( 'items' => Logger::fetch( 200 ) ) );
	}

	/**
	 * POST /admin/logs/clear
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_logs_clear() {
		return rest_ensure_response( array( 'success' => true, 'deleted' => Logger::clear() ) );
	}

	/**
	 * Detailed admin health check.
	 *
	 * @return \WP_REST_Response
	 */
	public function admin_health() {
		$checks = array();

		$checks['wordpress'] = array(
			'label'   => __( 'WordPress', 'ai-website-chatbot' ),
			'status'  => 'ok',
			'detail'  => get_bloginfo( 'version' ),
		);
		$checks['php'] = array(
			'label'   => __( 'PHP version', 'ai-website-chatbot' ),
			'status'  => version_compare( PHP_VERSION, '8.1', '>=' ) ? 'ok' : 'error',
			'detail'  => PHP_VERSION,
		);
		$checks['woocommerce'] = array(
			'label'   => __( 'WooCommerce', 'ai-website-chatbot' ),
			'status'  => aiwc_is_woocommerce_active() ? 'ok' : 'warning',
			'detail'  => aiwc_is_woocommerce_active() ? '1' : __( 'Not installed', 'ai-website-chatbot' ),
		);
		$checks['api_key'] = array(
			'label'   => __( 'AI API key', 'ai-website-chatbot' ),
			'status'  => Settings::is_ai_configured() ? 'ok' : 'warning',
			'detail'  => Settings::is_ai_configured() ? __( 'Configured', 'ai-website-chatbot' ) : __( 'Not configured', 'ai-website-chatbot' ),
		);
		$checks['database'] = array(
			'label'   => __( 'Database tables', 'ai-website-chatbot' ),
			'status'  => ( count( Database::existing_tables() ) === count( Database::definitions() ) ) ? 'ok' : 'error',
			'detail'  => count( Database::existing_tables() ) . ' / ' . count( Database::definitions() ),
		);
		$checks['rest'] = array(
			'label'   => __( 'REST API', 'ai-website-chatbot' ),
			'status'  => ( get_option( 'permalink_structure' ) || rest_url( self::NAMESPACE ) ) ? 'ok' : 'warning',
			'detail'  => esc_url( rest_url( self::NAMESPACE ) ),
		);
		$checks['cron'] = array(
			'label'   => __( 'Maintenance cron', 'ai-website-chatbot' ),
			'status'  => wp_next_scheduled( 'aiwc_maintenance' ) ? 'ok' : 'warning',
			'detail'  => wp_next_scheduled( 'aiwc_maintenance' ) ? wp_next_scheduled( 'aiwc_maintenance' ) : __( 'Not scheduled', 'ai-website-chatbot' ),
		);
		$checks['knowledge'] = array(
			'label'   => __( 'Knowledge base', 'ai-website-chatbot' ),
			'status'  => Knowledge_Base::count() > 0 ? 'ok' : 'warning',
			'detail'  => Knowledge_Base::count() . ' ' . __( 'items', 'ai-website-chatbot' ),
		);

		if ( Settings::is_ai_configured() ) {
			$connectivity = AI_Client::test_connection();
			$checks['connectivity'] = array(
				'label'   => __( 'AI connectivity', 'ai-website-chatbot' ),
				'status'  => is_wp_error( $connectivity ) ? 'error' : 'ok',
				'detail'  => is_wp_error( $connectivity ) ? $connectivity->get_error_message() : __( 'Connected', 'ai-website-chatbot' ),
			);
		}

		return rest_ensure_response( array( 'checks' => $checks ) );
	}

	/**
	 * Extract request parameters.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @param array            $keys    Keys.
	 * @return array
	 */
	private function request_params( \WP_REST_Request $request, array $keys ): array {
		$out = array();
		foreach ( $keys as $key ) {
			if ( 'history' === $key ) {
				$out[ $key ] = $request->get_param( $key );
				continue;
			}
			$out[ $key ] = $request->get_param( $key );
		}

		return $out;
	}

	/**
	 * Target user id for a public request.
	 *
	 * @return int
	 */
	private function target_user_id(): int {
		return is_user_logged_in() ? get_current_user_id() : 0;
	}

	/**
	 * Build a WP_Error response.
	 *
	 * @param \WP_Error $error Error.
	 * @return \WP_REST_Response
	 */
	private function error_response( \WP_Error $error ) {
		$data = $error->get_error_data();
		$status = isset( $data['status'] ) ? (int) $data['status'] : 400;

		return new \WP_REST_Response(
			array(
				'success' => false,
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			$status
		);
	}

	/**
	 * Mask a secret for display.
	 *
	 * @param string $secret Secret.
	 * @return string
	 */
	private static function mask( string $secret ): string {
		if ( '' === $secret ) {
			return '';
		}
		$len = strlen( $secret );

		return substr( $secret, 0, 4 ) . str_repeat( '•', 8 ) . ( $len > 8 ? substr( $secret, -4 ) : '' );
	}
}