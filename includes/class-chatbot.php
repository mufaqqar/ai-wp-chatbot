<?php
/**
 * Chat orchestration.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates a single chat turn: validation, retrieval, AI request, storage,
 * lead capture and response assembly.
 */
class Chatbot {

	/**
	 * Process a chat turn.
	 *
	 * @param array $payload Request payload.
	 * @param int   $user_id Current user id (0 for visitors).
	 * @return array|\WP_Error
	 */
	public static function process( array $payload, int $user_id = 0 ) {
		if ( ! (bool) Settings::get_setting( 'general.enabled', true ) ) {
			return new \WP_Error( 'aiwc_disabled', __( 'The chatbot is currently unavailable.', 'ai-website-chatbot' ), array( 'status' => 503 ) );
		}

		$message = Security::sanitize_message( $payload['message'] ?? '' );
		if ( is_wp_error( $message ) ) {
			return $message;
		}

		if ( Security::contains_markup( $message ) ) {
			return new \WP_Error( 'aiwc_invalid_message', __( 'That message contains unsupported content.', 'ai-website-chatbot' ), array( 'status' => 400 ) );
		}

		$limits = Security::check_limits( 'chat' );
		if ( is_wp_error( $limits ) ) {
			return $limits;
		}

		$store      = (bool) Settings::get_setting( 'privacy.store_conversations', true );
		$conv_id    = substr( sanitize_text_field( (string) ( $payload['conversation_id'] ?? '' ) ), 0, 32 );
		$session_id = substr( sanitize_text_field( (string) ( $payload['session_id'] ?? '' ) ), 0, 64 );
		$message_id = 0;

		if ( $store ) {
			$conversation = Conversations::start( $conv_id, $session_id, $user_id );
			$conv_id      = (string) $conversation['conversation_id'];
			$history      = Conversations::history( $conv_id );
			Conversations::add_message( $conv_id, 'user', $message );
		} else {
			if ( '' === $conv_id ) {
				$conv_id = aiwc_generate_id( 32 );
			}
			$history = array();
			if ( isset( $payload['history'] ) && is_array( $payload['history'] ) ) {
				foreach ( array_slice( $payload['history'], -20 ) as $entry ) {
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$history[] = array(
						'role'    => ( 'assistant' === ( $entry['role'] ?? '' ) ) ? 'assistant' : 'user',
						'message' => sanitize_textarea_field( (string) ( $entry['message'] ?? '' ) ),
					);
				}
			}
		}

		/**
		 * Fires before an AI request is made.
		 *
		 * @param array $payload Payload.
		 */
		do_action( 'ai_chatbot_before_ai_request', $payload );

		$built  = Prompt_Builder::build_messages( $message, $history );
		$result = AI_Client::chat( $built['messages'] );

		if ( is_wp_error( $result ) ) {
			Logger::error( 'chat', 'AI request failed', array( 'code' => $result->get_error_code() ) );

			return $result;
		}

		$content       = trim( (string) $result['content'] );
		$had_fallback  = Prompt_Builder::is_fallback( $content );
		$response_time = (float) ( $result['elapsed'] ?? 0 );
		$sources       = self::format_sources( $built['sources'] );

		if ( $store ) {
			/**
			 * Fires before a message is saved.
			 *
			 * @param string $content Assistant content.
			 * @param string $conv_id Conversation id.
			 */
			do_action( 'ai_chatbot_before_save_message', $content, $conv_id );

			$message_id = Conversations::add_message(
				$conv_id,
				'assistant',
				$content,
				array(
					'sources'       => $sources,
					'response_time' => $response_time,
					'usage_data'    => $result['usage'] ?? array(),
					'had_fallback'  => $had_fallback,
				)
			);
			Conversations::touch_by_conversation_id( $conv_id );
		}

		self::maybe_capture_lead( $payload, $conv_id );

		/**
		 * Fires after an AI response is produced.
		 *
		 * @param array  $response Response data.
		 * @param string $conv_id  Conversation id.
		 */
		$response = array(
			'success'         => true,
			'message'         => $content,
			'conversation_id' => $conv_id,
			'message_id'      => $message_id,
			'sources'         => $sources,
			'had_fallback'    => $had_fallback,
			'response_time'   => round( $response_time, 2 ),
		);

		do_action( 'ai_chatbot_after_ai_response', $response, $conv_id );

		/**
		 * Filter the final chat response returned to the browser.
		 *
		 * @param array $response Response.
		 */
		return apply_filters( 'ai_chatbot_chat_response', $response );
	}

	/**
	 * Run an admin test chat and return diagnostics.
	 *
	 * @param array $payload Payload.
	 * @return array|\WP_Error
	 */
	public static function test( array $payload ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'aiwc_forbidden', __( 'You are not allowed to run this test.', 'ai-website-chatbot' ), array( 'status' => 403 ) );
		}

		$message = Security::sanitize_message( $payload['message'] ?? '' );
		if ( is_wp_error( $message ) ) {
			return $message;
		}

		$built  = Prompt_Builder::build_messages( $message );
		$result = AI_Client::chat( $built['messages'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$content = trim( (string) $result['content'] );

		return array(
			'success'       => true,
			'message'       => $content,
			'sources'       => self::format_sources( $built['sources'] ),
			'response_time' => round( (float) ( $result['elapsed'] ?? 0 ), 2 ),
			'usage'         => $result['usage'] ?? array(),
			'had_fallback'  => Prompt_Builder::is_fallback( $content ),
		);
	}

	/**
	 * Convert the source map into a list of links.
	 *
	 * @param array $sources URL => title map.
	 * @return array
	 */
	private static function format_sources( array $sources ): array {
		if ( ! (bool) Settings::get_setting( 'knowledge.show_sources', true ) ) {
			return array();
		}

		$out = array();
		foreach ( $sources as $url => $title ) {
			$out[] = array(
				'title' => (string) $title,
				'url'   => ( 0 === strpos( (string) $url, '#' ) ) ? '' : esc_url_raw( (string) $url ),
			);
		}

		return $out;
	}

	/**
	 * Capture a lead if the visitor supplied contact fields.
	 *
	 * @param array  $payload Payload.
	 * @param string $conv_id Conversation id.
	 * @return void
	 */
	private static function maybe_capture_lead( array $payload, string $conv_id ): void {
		if ( ! (bool) Settings::get_setting( 'general.collect_leads', true ) ) {
			return;
		}

		$name  = trim( (string) ( $payload['name'] ?? '' ) );
		$email = trim( (string) ( $payload['email'] ?? '' ) );

		if ( '' === $name || '' === $email ) {
			return;
		}

		Leads::add(
			array(
				'name'            => $name,
				'email'           => $email,
				'phone'           => $payload['phone'] ?? '',
				'message'         => $payload['lead_message'] ?? '',
				'conversation_id' => $conv_id,
				'source'          => 'chatbot',
			)
		);
	}
}