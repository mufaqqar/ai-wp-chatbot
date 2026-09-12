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

$provider = (string) \aiwc_get_setting( 'ai.provider', 'openai' );
$model    = (string) \aiwc_get_setting( 'ai.model', 'gpt-4o-mini' );
$api_key  = \AIWebsiteChatbot\Settings::get_api_key( $provider );
$masked   = '' !== $api_key ? substr( $api_key, 0, 4 ) . str_repeat( '•', 8 ) . substr( $api_key, -4 ) : '';

$openai_models = array(
	'gpt-4o-mini',
	'gpt-4o',
	'gpt-4.1-mini',
	'gpt-4.1',
	'gpt-4-turbo',
);

$openrouter_free_models = array(
	'openrouter/free',
	'openai/gpt-4o-mini:free',
	'google/gemini-2.0-flash-001:free',
	'google/gemini-2.5-flash:free',
	'meta-llama/llama-3.3-70b-instruct:free',
	'nvidia/nemotron-3-ultra-550b-a55b:free',
	'nvidia/nemotron-3-super-120b-a12b:free',
	'qwen/qwen3-next-80b-a3b-instruct:free',
	'qwen/qwen3-coder:free',
	'nousresearch/hermes-3-llama-3.1-405b:free',
	'cohere/north-mini-code:free',
	'poolside/laguna-m.1:free',
	'meta-llama/llama-3.2-3b-instruct:free',
	'google/gemma-3-27b-it:free',
);
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'AI Configuration', 'ai-website-chatbot' ); ?></h1>

	<?php if ( isset( $_GET['aiwc_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success"><p><?php esc_html_e( 'API key saved.', 'ai-website-chatbot' ); ?></p></div>
	<?php endif; ?>

	<div class="notice notice-info"><p><?php esc_html_e( 'Pick a provider below, then save its API key, then save the AI settings (provider + model).', 'ai-website-chatbot' ); ?></p></div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'aiwc_save_api_key' ); ?>
		<input type="hidden" name="action" value="aiwc_save_api_key" />
		<input type="hidden" name="aiwc_provider" id="aiwc_provider_for_key" value="<?php echo esc_attr( $provider ); ?>" />
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_api_key"><?php esc_html_e( 'API key', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<input type="password" class="regular-text" id="aiwc_api_key" name="aiwc_api_key" value="" autocomplete="off" />
					<?php if ( '' !== $masked ) : ?>
						<p class="description">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: masked key */
									__( 'Saved key: %s (leave blank to keep it).', 'ai-website-chatbot' ),
									$masked
								)
							);
							?>
						</p>
					<?php endif; ?>
					<p class="description" id="aiwc_api_key_hint">
						<?php if ( 'openrouter' === $provider ) : ?>
							<?php
							echo wp_kses_post( sprintf(
								/* translators: %s: link to OpenRouter keys page */
								__( 'Get a free OpenRouter API key at <a href="%s" target="_blank" rel="noopener noreferrer">openrouter.ai/keys</a> — no credit card required.', 'ai-website-chatbot' ),
								'https://openrouter.ai/keys'
							) );
							?>
						<?php else : ?>
							<?php
							echo wp_kses_post( sprintf(
								/* translators: %s: link to OpenAI keys page */
								__( 'OpenAI API keys come from <a href="%s" target="_blank" rel="noopener noreferrer">platform.openai.com/api-keys</a>.', 'ai-website-chatbot' ),
								'https://platform.openai.com/api-keys'
							) );
							?>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>
		<?php submit_button( __( 'Save API key', 'ai-website-chatbot' ) ); ?>
	</form>

	<form method="post" action="options.php">
		<?php settings_fields( 'aiwc_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_provider"><?php esc_html_e( 'Provider', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<select id="aiwc_provider" name="aiwc_settings[ai][provider]">
						<option value="openai" <?php selected( $s( 'ai.provider' ), 'openai' ); ?>><?php esc_html_e( 'OpenAI — GPT models (paid / cheap)', 'ai-website-chatbot' ); ?></option>
						<option value="openrouter" <?php selected( $s( 'ai.provider' ), 'openrouter' ); ?>><?php esc_html_e( 'OpenRouter — Free models (:free)', 'ai-website-chatbot' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_model"><?php esc_html_e( 'Model', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<input class="regular-text" id="aiwc_model" name="aiwc_settings[ai][model]" list="aiwc_models_list" value="<?php echo esc_attr( $model ); ?>" placeholder="gpt-4o-mini" />
					<datalist id="aiwc_models_list">
						<?php foreach ( $openai_models as $m ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>"></option>
						<?php endforeach; ?>
						<?php foreach ( $openrouter_free_models as $m ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>"></option>
						<?php endforeach; ?>
					</datalist>
					<p class="description" id="aiwc_model_hint">
						<?php if ( 'openrouter' === $provider ) : ?>
							<?php esc_html_e( 'Choose any free (:free) model. openrouter/free auto-picks a free model for you.', 'ai-website-chatbot' ); ?>
							<br />
							<?php
							echo wp_kses_post( sprintf(
								/* translators: %s: link to OpenRouter models API */
								__( 'Free models rotate monthly — see the current list at <a href="%s" target="_blank" rel="noopener noreferrer">openrouter.ai/api/v1/models</a>. You can type any model ID.', 'ai-website-chatbot' ),
								'https://openrouter.ai/api/v1/models'
							) );
							?>
						<?php else : ?>
							<?php esc_html_e( 'You can also type any custom model ID supported by your OpenAI account.', 'ai-website-chatbot' ); ?>
						<?php endif; ?>
					</p>
				</td>
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

<script>
(function () {
	var providerSelect = document.getElementById('aiwc_provider');
	var modelInput     = document.getElementById('aiwc_model');
	var keyProvider    = document.getElementById('aiwc_provider_for_key');
	if (!providerSelect || !modelInput || !keyProvider) { return; }

	var defaultModel = { openai: 'gpt-4o-mini', openrouter: 'openrouter/free' };
	var hints = {
		openai: 'OpenAI API keys come from platform.openai.com/api-keys.',
		openrouter: 'Get a free OpenRouter API key at openrouter.ai/keys — no credit card required.'
	};
	var modelHints = {
		openai: 'You can type any custom model ID supported by your OpenAI account.',
		openrouter: 'Choose any free (:free) model. openrouter/free auto-picks a free model for you. Free models rotate monthly — see the current list at openrouter.ai/api/v1/models.'
	};
	var keyLabels = { openai: 'OpenAI API key', openrouter: 'OpenRouter API key' };

	function sync() {
		var p = providerSelect.value;
		keyProvider.value = p;

		var current = modelInput.value.trim();
		if (current === '' || current === 'gpt-4o-mini' || current === 'openrouter/free') {
			modelInput.value = defaultModel[p] || 'gpt-4o-mini';
		}

		var label = document.querySelector('label[for="aiwc_api_key"]');
		if (label && keyLabels[p]) { label.textContent = keyLabels[p]; }

		var hint = document.getElementById('aiwc_api_key_hint');
		if (hint && hints[p]) { hint.textContent = hints[p]; }

		var modelHint = document.getElementById('aiwc_model_hint');
		if (modelHint && modelHints[p]) { modelHint.textContent = modelHints[p]; }
	}

	providerSelect.addEventListener('change', sync);
})();
</script>