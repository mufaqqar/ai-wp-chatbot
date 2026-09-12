<?php
/**
 * Help view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Help', 'ai-website-chatbot' ); ?></h1>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Quick start', 'ai-website-chatbot' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Add your OpenAI API key on the AI Configuration page.', 'ai-website-chatbot' ); ?></li>
			<li><?php esc_html_e( 'Click “Index Website” on the Knowledge Base page to build the index.', 'ai-website-chatbot' ); ?></li>
			<li><?php esc_html_e( 'Optionally add FAQs and enable WooCommerce integration.', 'ai-website-chatbot' ); ?></li>
			<li><?php esc_html_e( 'The widget appears automatically in the bottom corner of your site.', 'ai-website-chatbot' ); ?></li>
		</ol>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Embedding', 'ai-website-chatbot' ); ?></h2>
		<p><?php esc_html_e( 'The widget loads automatically through the wp_footer hook. You can also place it anywhere:', 'ai-website-chatbot' ); ?></p>
		<pre class="code">[ai_chatbot]</pre>
		<p><?php esc_html_e( 'Or from a template:', 'ai-website-chatbot' ); ?></p>
		<pre class="code">&lt;?php do_action( 'ai_chatbot_render' ); ?&gt;</pre>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Shortcode & hooks', 'ai-website-chatbot' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Hook / Filter', 'ai-website-chatbot' ); ?></th><th><?php esc_html_e( 'Description', 'ai-website-chatbot' ); ?></th></tr></thead>
			<tbody>
			<tr><td><code>ai_chatbot_render</code></td><td><?php esc_html_e( 'Action — renders the widget inline, e.g. inside a template.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_widget_config</code></td><td><?php esc_html_e( 'Filter — customize the configuration sent to the browser.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_build_prompt</code></td><td><?php esc_html_e( 'Filter — customize the system prompt.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_retrieve_knowledge</code></td><td><?php esc_html_e( 'Filter — replace or override knowledge retrieval.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_chat_response</code></td><td><?php esc_html_e( 'Filter — modify the final chat response.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_ai_client</code></td><td><?php esc_html_e( 'Filter — supply a custom AI provider client.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_before_ai_request</code></td><td><?php esc_html_e( 'Action — fires before each AI request.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_after_ai_response</code></td><td><?php esc_html_e( 'Action — fires after each AI response.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_before_save_message</code></td><td><?php esc_html_e( 'Action — fires before an assistant message is stored.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_after_lead</code></td><td><?php esc_html_e( 'Action — fires after a lead is captured.', 'ai-website-chatbot' ); ?></td></tr>
			<tr><td><code>ai_chatbot_default_settings</code></td><td><?php esc_html_e( 'Filter — change the default settings array.', 'ai-website-chatbot' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Health check', 'ai-website-chatbot' ); ?></h2>
		<p><button type="button" class="button button-primary" id="aiwc_health_run"><?php esc_html_e( 'Run health check', 'ai-website-chatbot' ); ?></button></p>
		<div id="aiwc_health_results"></div>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Debug logs', 'ai-website-chatbot' ); ?></h2>
		<p><button type="button" class="button" id="aiwc_logs_load"><?php esc_html_e( 'Load logs', 'ai-website-chatbot' ); ?></button>
		<button type="button" class="button" id="aiwc_logs_clear"><?php esc_html_e( 'Clear logs', 'ai-website-chatbot' ); ?></button></p>
		<div id="aiwc_logs_container" class="aiwc-logs"></div>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Privacy', 'ai-website-chatbot' ); ?></h2>
		<p class="description"><?php esc_html_e( 'The plugin stores only the data needed for chat history, knowledge and lead capture. Raw IP addresses are never stored; rate limiting uses expiring, hashed visitor fingerprints. Conversation history, leads and logs can be disabled or retained for a limited period from the Settings page.', 'ai-website-chatbot' ); ?></p>
	</div>
</div>