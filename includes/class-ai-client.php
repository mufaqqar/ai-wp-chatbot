<?php
/**
 * AI provider client (OpenAI-compatible chat completions).
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and executes AI requests. The provider layer is extensible through the
 * ai_chatbot_ai_client filter, allowing additional providers to be registered.
 */
class AI_Client {

	/**
	 * Send a chat completion request.
	 *
	 * @param array $messages List of role/content message arrays.
	 * @param array $options  Optional overrides (model, temperature, max_tokens, timeout, retries).
	 * @return array|\WP_Error Array with keys content, usage, raw on success.
	 */
	public static function chat( array $messages, array $options = array() ) {
		$provider = Settings::get_setting( 'ai.provider', 'openai' );

		/**
		 * Filter the AI client so alternate providers can be supplied.
		 *
		 * @param null  $client   Custom client object.
		 * @param array $messages Messages.
		 * @param array $options  Options.
		 */
		$client = apply_filters( 'ai_chatbot_ai_client', null, $messages, $options );
		if ( is_object( $client ) && method_exists( $client, 'chat' ) ) {
			return $client->chat( $messages, $options );
		}

		if ( 'openrouter' === $provider ) {
			return self::openrouter_chat( $messages, $options );
		}

		if ( 'openai' !== $provider ) {
			return new \WP_Error( 'aiwc_unknown_provider', __( 'The configured AI provider is not supported.', 'ai-website-chatbot' ) );
		}

		return self::openai_chat( $messages, $options );
	}

	/**
	 * Execute an OpenAI chat completions request.
	 *
	 * @param array $messages Messages.
	 * @param array $options  Options.
	 * @return array|\WP_Error
	 */
	private static function openai_chat( array $messages, array $options = array() ) {
		$api_key = Settings::get_api_key( 'openai' );
		if ( '' === $api_key ) {
			return new \WP_Error( 'aiwc_no_api_key', __( 'The AI provider is not configured yet.', 'ai-website-chatbot' ) );
		}

		$endpoint = apply_filters( 'ai_chatbot_ai_endpoint', 'https://api.openai.com/v1/chat/completions' );

		return self::send_completions(
			$endpoint,
			array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			$messages,
			$options
		);
	}

	/**
	 * Execute an OpenRouter chat completions request. OpenRouter exposes many
	 * free (:free / openrouter/free) models through an OpenAI-compatible API.
	 *
	 * @param array $messages Messages.
	 * @param array $options  Options.
	 * @return array|\WP_Error
	 */
	private static function openrouter_chat( array $messages, array $options = array() ) {
		$api_key = Settings::get_api_key( 'openrouter' );
		if ( '' === $api_key ) {
			return new \WP_Error( 'aiwc_no_api_key', __( 'The AI provider is not configured yet.', 'ai-website-chatbot' ) );
		}

		$endpoint = apply_filters( 'ai_chatbot_ai_endpoint', 'https://openrouter.ai/api/v1/chat/completions' );

		return self::send_completions(
			$endpoint,
			array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
				'HTTP-Referer'  => home_url( '/' ),
				'X-Title'       => wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
			),
			$messages,
			$options
		);
	}

	/**
	 * Send a chat completion request to an OpenAI-compatible endpoint with
	 * retry/backoff and structured error handling.
	 *
	 * @param string $endpoint Full API endpoint URL.
	 * @param array  $headers  Request headers (Authorization prepared by caller).
	 * @param array  $messages Messages.
	 * @param array  $options  Options.
	 * @return array|\WP_Error
	 */
	private static function send_completions( string $endpoint, array $headers, array $messages, array $options = array() ) {
		$model       = (string) ( $options['model'] ?? Settings::get_setting( 'ai.model', 'gpt-4o-mini' ) );
		$temperature = (float) ( $options['temperature'] ?? Settings::get_setting( 'ai.temperature', 0.3 ) );
		$max_tokens  = (int) ( $options['max_tokens'] ?? Settings::get_setting( 'ai.max_tokens', 500 ) );
		$timeout     = (int) ( $options['timeout'] ?? Settings::get_setting( 'ai.timeout', 30 ) );
		$retries     = (int) ( $options['retries'] ?? Settings::get_setting( 'ai.retries', 1 ) );

		$body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		/**
		 * Filter the request body before sending to the provider.
		 *
		 * @param array $body     Request body.
		 * @param array $messages Messages.
		 */
		$body = apply_filters( 'ai_chatbot_ai_request_body', $body, $messages );

		$args = array(
			'timeout' => $timeout,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
		);

		$attempt = 0;
		$last_error = null;

		do {
			$started  = microtime( true );
			$response = wp_remote_post( $endpoint, $args );
			$elapsed  = microtime( true ) - $started;

			if ( is_wp_error( $response ) ) {
				$last_error = new \WP_Error( 'aiwc_api_unavailable', __( 'The AI service is currently unavailable. Please try again later.', 'ai-website-chatbot' ), array( 'code' => $response->get_error_code() ) );
				Logger::error( 'ai_request', 'AI request transport error', array( 'code' => $response->get_error_code() ) );
			} else {
				$status = (int) wp_remote_retrieve_response_code( $response );
				$raw    = (string) wp_remote_retrieve_body( $response );

				if ( 200 === $status ) {
					$data = json_decode( $raw, true );
					if ( ! is_array( $data ) || empty( $data['choices'][0]['message']['content'] ) ) {
						$last_error = new \WP_Error( 'aiwc_invalid_response', __( 'The AI service returned an unexpected response.', 'ai-website-chatbot' ) );
						Logger::error( 'ai_request', 'Invalid AI response', array( 'status' => $status ) );
					} else {
						$usage = isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array();
						Logger::info( 'ai_request', 'AI request complete', array( 'status' => $status, 'elapsed' => round( $elapsed, 3 ), 'model' => $model ) );

						return array(
							'content' => (string) $data['choices'][0]['message']['content'],
							'usage'   => $usage,
							'model'   => $model,
							'elapsed' => $elapsed,
						);
					}
				} elseif ( in_array( $status, array( 429, 500, 502, 503, 504 ), true ) ) {
					$last_error = new \WP_Error( 'aiwc_api_rate_limit', __( 'The AI service is busy. Please try again in a moment.', 'ai-website-chatbot' ), array( 'status' => $status ) );
					Logger::error( 'ai_request', 'AI service busy', array( 'status' => $status ) );
				} elseif ( 401 === $status ) {
					Logger::error( 'ai_request', 'AI authentication failed', array( 'status' => $status ) );

					return new \WP_Error( 'aiwc_invalid_key', __( 'The AI provider rejected the API key. Please check your configuration.', 'ai-website-chatbot' ) );
				} else {
					$error_data    = json_decode( $raw, true );
					$error_message = isset( $error_data['error']['message'] ) && is_string( $error_data['error']['message'] ) ? trim( $error_data['error']['message'] ) : '';
					$last_error    = new \WP_Error(
						'aiwc_api_error',
						'' !== $error_message ? $error_message : __( 'The AI service could not process the request.', 'ai-website-chatbot' ),
						array( 'status' => $status )
					);
					Logger::error( 'ai_request', 'AI service error', array( 'status' => $status, 'detail' => $error_message ) );
				}
			}

			++$attempt;
			if ( $attempt <= $retries && null !== $last_error ) {
				$delay_ms = (int) apply_filters( 'ai_chatbot_ai_retry_delay', 500 * ( 2 ** ( $attempt - 1 ) ) );
				usleep( min( 5000, $delay_ms ) * 1000 );
			}
		} while ( $attempt <= $retries );

		return $last_error ?: new \WP_Error( 'aiwc_api_error', __( 'The AI service could not process the request.', 'ai-website-chatbot' ) );
	}

	/**
	 * Lightweight connectivity test used by the admin health check.
	 *
	 * @return true|\WP_Error
	 */
	public static function test_connection() {
		if ( ! Settings::is_ai_configured() ) {
			return new \WP_Error( 'aiwc_no_api_key', __( 'No API key configured.', 'ai-website-chatbot' ) );
		}

		$result = self::chat(
			array(
				array( 'role' => 'system', 'content' => 'Reply with the single word: OK' ),
				array( 'role' => 'user', 'content' => 'Health check.' ),
			),
			array( 'max_tokens' => 5, 'temperature' => 0, 'timeout' => 15, 'retries' => 0 )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}
}