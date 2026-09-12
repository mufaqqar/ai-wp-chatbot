<?php
/**
 * AI Configuration view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

$s = function ( $key, $default = '' ) {
	return \aiwc_get_setting( $key, $default );
};

$api_key = \AIWebsiteChatbot\Settings::get_api_key();
$masked  = '' !== $api_key ? substr( $api_key, 0, 4 ) . str_repeat( '•', 8 ) . substr( $api_key, -4 ) : '';
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'AI Configuration', 'ai-website-chatbot' ); ?></h1>

	<?php if ( isset( $_GET['aiwc_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'API key saved.', 'ai-website-chatbot' ); ?></p></div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'API key', 'ai-website-chatbot' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'aiwc_save_api_key' ); ?>
		<input type="hidden" name="action" value="aiwc_save_api_key" />
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_api_key"><?php esc_html_e( 'OpenAI API key', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<input type="password" class="regular-text" id="aiwc_api_key" name="aiwc_api_key" value="" autocomplete="off" />
					<?php if ( '' !== $masked ) : ?>
						<p class="description">
							<?php
							/* translators: %s: masked key */
							echo esc_html( sprintf( __( 'Saved key: %s (leave blank to keep it).', 'ai-website-chatbot' ), $masked ) );
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			</tbody>
		</table>
		<?php submit_button( __( 'Save API key', 'ai-website-chatbot' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'AI provider settings', 'ai-website-chatbot' ); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'aiwc_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_provider"><?php esc_html_e( 'API provider', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<select id="aiwc_provider" name="aiwc_settings[ai][provider]">
						<option value="openai" <?php selected( $s( 'ai.provider' ), 'openai' ); ?>>OpenAI</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_model"><?php esc_html_e( 'Model', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_model" name="aiwc_settings[ai][model]" value="<?php echo esc_attr( $s( 'ai.model', 'gpt-4o-mini' ) ); ?>" placeholder="gpt-4o-mini" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_temp"><?php esc_html_e( 'Temperature', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" step="0.1" min="0" max="2" id="aiwc_temp" name="aiwc_settings[ai][temperature]" value="<?php echo esc_attr( $s( 'ai.temperature', 0.3 ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_tokens"><?php esc_html_e( 'Maximum output tokens', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_tokens" name="aiwc_settings[ai][max_tokens]" value="<?php echo (int) $s( 'ai.max_tokens', 500 ); ?>" min="1" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_timeout"><?php esc_html_e( 'Timeout (seconds)', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_timeout" name="aiwc_settings[ai][timeout]" value="<?php echo (int) $s( 'ai.timeout', 30 ); ?>" min="5" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_retries"><?php esc_html_e( 'Retry count', 'ai-website-chatbot' ); ?></label></th>
				<td><input type="number" id="aiwc_retries" name="aiwc_settings[ai][retries]" value="<?php echo (int) $s( 'ai.retries', 1 ); ?>" min="0" max="5" /></td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Test chat', 'ai-website-chatbot' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Send a test question to verify your configuration and inspect the retrieved sources.', 'ai-website-chatbot' ); ?></p>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_test_message"><?php esc_html_e( 'Test question', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_test_message" value="" placeholder="<?php esc_attr_e( 'What services do we offer?', 'ai-website-chatbot' ); ?>" />
					<button type="button" class="button" id="aiwc_test_run"><?php esc_html_e( 'Run test', 'ai-website-chatbot' ); ?></button>
				</td>
			</tr>
			</tbody>
		</table>
		<div id="aiwc_test_result" class="aiwc-test-result" hidden></div>

		<?php submit_button( __( 'Save AI settings', 'ai-website-chatbot' ) ); ?>
	</form>
</div>