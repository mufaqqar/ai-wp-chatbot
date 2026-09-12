<?php
/**
 * Knowledge Base view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Knowledge Base', 'ai-website-chatbot' ); ?></h1>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Index website content', 'ai-website-chatbot' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Index your pages, posts, custom post types, products and FAQs so the chatbot can answer from your content. Indexing runs in batches to avoid freezing the browser.', 'ai-website-chatbot' ); ?>
		</p>
		<p>
			<button type="button" class="button button-primary" id="aiwc_index_start"><?php esc_html_e( 'Index Website', 'ai-website-chatbot' ); ?></button>
			<button type="button" class="button" id="aiwc_index_step" disabled><?php esc_html_e( 'Continue indexing', 'ai-website-chatbot' ); ?></button>
		</p>
		<div id="aiwc_index_progress" class="aiwc-progress" hidden>
			<div class="aiwc-progress-bar"><span id="aiwc_progress_fill"></span></div>
			<p id="aiwc_progress_text"></p>
		</div>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Indexed content', 'ai-website-chatbot' ); ?></h2>
		<div class="aiwc-filters">
			<input type="search" id="aiwc_knowledge_search" placeholder="<?php esc_attr_e( 'Search knowledge…', 'ai-website-chatbot' ); ?>" />
			<select id="aiwc_knowledge_type">
				<option value=""><?php esc_html_e( 'All types', 'ai-website-chatbot' ); ?></option>
			</select>
			<select id="aiwc_knowledge_status">
				<option value=""><?php esc_html_e( 'All statuses', 'ai-website-chatbot' ); ?></option>
				<option value="active"><?php esc_html_e( 'Active', 'ai-website-chatbot' ); ?></option>
				<option value="inactive"><?php esc_html_e( 'Inactive', 'ai-website-chatbot' ); ?></option>
			</select>
			<button type="button" class="button" id="aiwc_knowledge_refresh"><?php esc_html_e( 'Refresh', 'ai-website-chatbot' ); ?></button>
		</div>
		<table class="wp-list-table widefat striped" id="aiwc_knowledge_table">
			<thead><tr>
				<th><?php esc_html_e( 'Title', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Type', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Length', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Status', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Indexed', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ai-website-chatbot' ); ?></th>
			</tr></thead>
			<tbody><tr><td colspan="6"><?php esc_html_e( 'Loading…', 'ai-website-chatbot' ); ?></td></tr></tbody>
		</table>
		<div class="aiwc-pagination" id="aiwc_knowledge_pagination"></div>
	</div>
</div>