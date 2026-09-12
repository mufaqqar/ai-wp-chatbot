<?php
/**
 * Analytics view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Analytics', 'ai-website-chatbot' ); ?></h1>

	<div class="aiwc-cards" id="aiwc_analytics_cards"></div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Last 14 days', 'ai-website-chatbot' ); ?></h2>
		<div id="aiwc_chart"></div>
	</div>

	<div class="aiwc-grid-2">
		<div class="aiwc-panel">
			<h2><?php esc_html_e( 'Popular questions', 'ai-website-chatbot' ); ?></h2>
			<table class="wp-list-table widefat striped" id="aiwc_popular_table">
				<thead><tr><th><?php esc_html_e( 'Question', 'ai-website-chatbot' ); ?></th><th><?php esc_html_e( 'Times asked', 'ai-website-chatbot' ); ?></th></tr></thead>
				<tbody><tr><td colspan="2"><?php esc_html_e( 'Loading…', 'ai-website-chatbot' ); ?></td></tr></tbody>
			</table>
		</div>
		<div class="aiwc-panel">
			<h2><?php esc_html_e( 'Unanswered questions', 'ai-website-chatbot' ); ?></h2>
			<table class="wp-list-table widefat striped" id="aiwc_failed_table">
				<thead><tr><th><?php esc_html_e( 'Question', 'ai-website-chatbot' ); ?></th><th><?php esc_html_e( 'Times', 'ai-website-chatbot' ); ?></th></tr></thead>
				<tbody><tr><td colspan="2"><?php esc_html_e( 'Loading…', 'ai-website-chatbot' ); ?></td></tr></tbody>
			</table>
		</div>
	</div>
</div>