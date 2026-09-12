<?php
/**
 * Leads view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Leads', 'ai-website-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Leads submitted through the chatbot. Update their status as you contact them.', 'ai-website-chatbot' ); ?></p>

	<div class="aiwc-filters">
		<input type="search" id="aiwc_lead_search" placeholder="<?php esc_attr_e( 'Search leads…', 'ai-website-chatbot' ); ?>" />
		<select id="aiwc_lead_status">
			<option value=""><?php esc_html_e( 'All statuses', 'ai-website-chatbot' ); ?></option>
			<option value="new"><?php esc_html_e( 'New', 'ai-website-chatbot' ); ?></option>
			<option value="contacted"><?php esc_html_e( 'Contacted', 'ai-website-chatbot' ); ?></option>
			<option value="qualified"><?php esc_html_e( 'Qualified', 'ai-website-chatbot' ); ?></option>
			<option value="converted"><?php esc_html_e( 'Converted', 'ai-website-chatbot' ); ?></option>
			<option value="closed"><?php esc_html_e( 'Closed', 'ai-website-chatbot' ); ?></option>
		</select>
		<button type="button" class="button" id="aiwc_lead_refresh"><?php esc_html_e( 'Refresh', 'ai-website-chatbot' ); ?></button>
		<a class="button button-secondary" id="aiwc_lead_export" href="#"><?php esc_html_e( 'Export CSV', 'ai-website-chatbot' ); ?></a>
	</div>

	<table class="wp-list-table widefat striped" id="aiwc_lead_table">
		<thead><tr>
			<th><?php esc_html_e( 'Name', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Email', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Message', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Status', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Date', 'ai-website-chatbot' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'ai-website-chatbot' ); ?></th>
		</tr></thead>
		<tbody><tr><td colspan="7"><?php esc_html_e( 'Loading…', 'ai-website-chatbot' ); ?></td></tr></tbody>
	</table>
	<div class="aiwc-pagination" id="aiwc_lead_pagination"></div>
</div>