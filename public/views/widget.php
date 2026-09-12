<?php
/**
 * Chat widget DOM used by the frontend scripts.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="aiwc-widget" class="aiwc-widget" aria-live="polite">
	<div class="aiwc-window" role="dialog" aria-label="<?php esc_attr_e( 'Chat with AI assistant', 'ai-website-chatbot' ); ?>" hidden>
		<header class="aiwc-header">
			<div class="aiwc-header-info">
				<span class="aiwc-avatar" aria-hidden="true"></span>
				<div>
					<strong class="aiwc-bot-name"></strong>
					<span class="aiwc-online"><span class="aiwc-online-dot"></span><span class="aiwc-online-text"></span></span>
				</div>
			</div>
			<div class="aiwc-header-actions">
				<button type="button" class="aiwc-icon-btn" data-aiwc-clear title="<?php esc_attr_e( 'Clear conversation', 'ai-website-chatbot' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
				</button>
				<button type="button" class="aiwc-icon-btn" data-aiwc-minimize title="<?php esc_attr_e( 'Minimize', 'ai-website-chatbot' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
				</button>
			</div>
		</header>

		<div class="aiwc-body">
			<div class="aiwc-messages" tabindex="0"></div>
			<div class="aiwc-footer">
				<div class="aiwc-input-row">
					<textarea class="aiwc-input" rows="1" placeholder="<?php esc_attr_e( 'Type your message…', 'ai-website-chatbot' ); ?>"></textarea>
					<button type="button" class="aiwc-send">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
					</button>
				</div>
			</div>
		</div>
	</div>
	<button type="button" class="aiwc-button" title="<?php esc_attr_e( 'Open chat', 'ai-website-chatbot' ); ?>">
		<span class="aiwc-button-icon">
			<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
		</span>
	</button>
</div>