<?php
/**
 * Conversations view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Conversations', 'ai-website-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Review recent visitor conversations. Deleting a conversation also removes its messages and any linked lead.', 'ai-website-chatbot' ); ?></p>

	<div class="aiwc-filters">
		<input type="search" id="aiwc_conv_search" placeholder="<?php esc_attr_e( 'Search conversations…', 'ai-website-chatbot' ); ?>" />
		<button type="button" class="button" id="aiwc_conv_refresh"><?php esc_html_e( 'Refresh', 'ai-website-chatbot' ); ?></button>
	</div>

	<div id="aiwc_conversation_list"></div>
	<div class="aiwc-pagination" id="aiwc_conv_pagination"></div>
</div>