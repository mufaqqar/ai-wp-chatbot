<?php
/**
 * Security helpers: rate limiting, nonces, sanitization.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Implements abuse protection for anonymous REST endpoints.
 * Rate limiting uses expiring transients keyed by an anonymized visitor
 * fingerprint (hashed IP + user agent). Raw IP addresses are never stored.
 */
class Security {

	public const NONCE_ACTION = 'aiwc_chat';

	/**
	 * Create a chat nonce.
	 *
	 * The public widget nonce is request-context independent so it verifies
	 * identically for anonymous and logged-in visitors. WordPress nonces are
	 * bound to the session token in the visitor's cookie (wp_get_session_token()
	 * is consulted even when the current user is anonymous), and REST requests
	 * without an X-WP-Nonce are treated as anonymous, so a nonce minted by the
	 * rendered page can never match the verification context. The widget
	 * therefore uses an HMAC token derived from the site salt and time window.
	 * Pass an explicit user ID to opt into a user-scoped WordPress nonce.
	 *
	 * @param int $user_id User ID to scope the nonce to (0 for visitors).
	 * @return string
	 */
	public static function create_nonce( int $user_id = 0 ): string {
		if ( $user_id > 0 ) {
			return wp_create_nonce( self::NONCE_ACTION . "_user_{$user_id}" );
		}

		return self::build_guest_nonce();
	}

	/**
	 * Build a guest nonce for the current time window.
	 *
	 * @return string
	 */
	private static function build_guest_nonce(): string {
		return substr( hash_hmac( 'sha256', wp_nonce_tick() . '|' . self::NONCE_ACTION . '_guest', self::secret_salt() ), 0, 10 );
	}

	/**
	 * Verify the chat nonce.
	 *
	 * @param int    $user_id Expected user ID (0 verifies the widget guest nonce).
	 * @param string $nonce   Submitted nonce.
	 * @return bool
	 */
	public static function verify_nonce( int $user_id = 0, string $nonce = '' ): bool {
		if ( '' === $nonce ) {
			return false;
		}

		if ( $user_id > 0 ) {
			return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION . "_user_{$user_id}" );
		}

		return self::verify_guest_nonce( $nonce );
	}

	/**
	 * Verify a widget guest nonce against the current and previous time window.
	 *
	 * @param string $nonce Submitted nonce.
	 * @return bool
	 */
	private static function verify_guest_nonce( string $nonce ): bool {
		$tick = wp_nonce_tick();

		return hash_equals( substr( hash_hmac( 'sha256', $tick . '|' . self::NONCE_ACTION . '_guest', self::secret_salt() ), 0, 10 ), $nonce )
			|| hash_equals( substr( hash_hmac( 'sha256', ( $tick - 1 ) . '|' . self::NONCE_ACTION . '_guest', self::secret_salt() ), 0, 10 ), $nonce );
	}

	/**
	 * Anonymized fingerprint for a visitor based on hashed IP and user agent.
	 *
	 * @param string $context Namespacing to avoid collisions.
	 * @return string
	 */
	public static function visitor_fingerprint( string $context = 'visitor' ): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		$agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$seed   = $remote . '|' . $agent . '|' . self::secret_salt();

		return $context . '_' . hash( 'sha256', $seed );
	}

	/**
	 * Site-specific secret used to salt fingerprints.
	 *
	 * @return string
	 */
	private static function secret_salt(): string {
		$salt = get_option( 'aiwc_fingerprint_salt', '' );

		if ( '' === $salt ) {
			$salt = wp_generate_password( 32, true, true );
			update_option( 'aiwc_fingerprint_salt', $salt );
		}

		return (string) $salt;
	}

	/**
	 * Increment a time-window counter and check it against a limit.
	 *
	 * @param string $key     Counter key (time window included).
	 * @param int    $limit   Maximum allowed in the window.
	 * @param int    $lifespan Lifetime of the transient window in seconds.
	 * @return bool True when within limits.
	 */
	private static function check_window( string $key, int $limit, int $lifespan ): bool {
		$current = (int) get_transient( $key );

		if ( $current >= $limit ) {
			return false;
		}

		set_transient( $key, $current + 1, $lifespan );

		return true;
	}

	/**
	 * Validate that the current visitor is within configured rate limits.
	 *
	 * @param string $context Rate limit context (e.g. "chat").
	 * @return bool|\WP_Error True when allowed, WP_Error when limited.
	 */
	public static function check_limits( string $context = 'chat' ) {
		$fp = self::visitor_fingerprint( 'rl' );

		$per_minute = (int) Settings::get_setting( 'rate_limits.messages_per_minute', 10 );
		$per_hour   = (int) Settings::get_setting( 'rate_limits.messages_per_hour', 100 );
		$per_day    = (int) Settings::get_setting( 'rate_limits.daily_message_limit', 500 );

		$minute_key = 'aiwc_rl_' . $context . '_' . $fp . '_' . gmdate( 'YmdHi' );
		$hour_key   = 'aiwc_rl_' . $context . '_' . $fp . '_' . gmdate( 'YmdH' );

		if ( ! self::check_window( $minute_key, $per_minute, MINUTE_IN_SECONDS ) ) {
			return new \WP_Error(
				'aiwc_rate_limited',
				__( 'Too many messages. Please wait a moment and try again.', 'ai-website-chatbot' ),
				array( 'status' => 429 )
			);
		}

		if ( ! self::check_window( $hour_key, $per_hour, HOUR_IN_SECONDS ) ) {
			return new \WP_Error(
				'aiwc_rate_limited',
				__( 'Message limit reached. Please try again later.', 'ai-website-chatbot' ),
				array( 'status' => 429 )
			);
		}

		if ( ! self::check_day_limit( $per_day ) ) {
			return new \WP_Error(
				'aiwc_daily_limit',
				__( 'The daily message limit has been reached. Please try again tomorrow.', 'ai-website-chatbot' ),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Site-wide daily dashboard limit stored in an option.
	 *
	 * @param int $limit Maximum daily messages across the site.
	 * @return bool
	 */
	private static function check_day_limit( int $limit ): bool {
		$today   = gmdate( 'Ymd' );
		$option  = get_option( 'aiwc_daily_counts', array( 'day' => '', 'count' => 0 ) );

		if ( ! is_array( $option ) || ( $option['day'] ?? '' ) !== $today ) {
			$option = array( 'day' => $today, 'count' => 0 );
		}

		if ( (int) $option['count'] >= $limit ) {
			return false;
		}

		$option['count'] = (int) $option['count'] + 1;
		update_option( 'aiwc_daily_counts', $option );

		return true;
	}

	/**
	 * Sanitize and validate an incoming chat message.
	 *
	 * @param mixed $message Raw message.
	 * @return string|\WP_Error
	 */
	public static function sanitize_message( $message ) {
		$clean = sanitize_textarea_field( wp_unslash( (string) $message ) );
		$clean = trim( $clean );

		if ( '' === $clean ) {
			return new \WP_Error( 'aiwc_empty_message', __( 'Message cannot be empty.', 'ai-website-chatbot' ), array( 'status' => 400 ) );
		}

		$max = (int) Settings::get_setting( 'rate_limits.max_message_length', 1000 );
		if ( mb_strlen( $clean ) > $max ) {
			return new \WP_Error(
				'aiwc_message_too_long',
				sprintf( __( 'Message is too long. Maximum length is %d characters.', 'ai-website-chatbot' ), $max ),
				array( 'status' => 400 )
			);
		}

		return $clean;
	}

	/**
	 * Reject obviously hostile prompt-injection attempts at the input layer.
	 *
	 * @param string $text Untrusted user text.
	 * @return bool True when content appears to be an injection attempt.
	 */
	public static function looks_like_injection( string $text ): bool {
		$patterns = array(
			'/ignore\s+(your|all|any|the)\s+(previous\s+)?(instructions|prompt|orders|rules)/i',
			'/show\s+(me\s+)?(your|the)\s+(system\s+)?prompt/i',
			'/reveal\s+(your|the)\s+system\s+prompt/i',
			'/forget\s+(all\s+)?(your\s+)?instructions/i',
			'/disregard\s+(all\s+)?(your\s+)?instructions/i',
			'/(api|secret|admin)\s*key/i',
			'/act\s+as\s+(an\s+|a\s+)?(developer\s+of\s+a\s+)?chatbot\s+and\s+/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Basic bot check: refuse requests with script-like payloads.
	 *
	 * @param string $text Raw text.
	 * @return bool
	 */
	public static function contains_markup( string $text ): bool {
		return (bool) preg_match( '/<(script|iframe|object|form|svg)[\s>]/i', $text );
	}

	/**
	 * Register or retrieve the REST nonce used by the frontend widget.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function public_nonce( int $user_id = 0 ): string {
		return self::create_nonce( $user_id );
	}
}