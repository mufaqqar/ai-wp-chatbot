<?php
/**
 * FAQs view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'FAQs', 'ai-website-chatbot' ); ?></h1>
	<p class="description"><?php esc_html_e( 'FAQs are treated as high-priority knowledge. Add common questions so the chatbot answers them directly.', 'ai-website-chatbot' ); ?></p>

	<div class="aiwc-panel">
		<h2 id="aiwc_faq_form_title"><?php esc_html_e( 'Add FAQ', 'ai-website-chatbot' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><label for="aiwc_faq_question"><?php esc_html_e( 'Question', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="large-text" id="aiwc_faq_question" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_faq_answer"><?php esc_html_e( 'Answer', 'ai-website-chatbot' ); ?></label></th>
				<td><textarea class="large-text" id="aiwc_faq_answer" rows="4"></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_faq_category"><?php esc_html_e( 'Category', 'ai-website-chatbot' ); ?></label></th>
				<td><input class="regular-text" id="aiwc_faq_category" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aiwc_faq_status"><?php esc_html_e( 'Status', 'ai-website-chatbot' ); ?></label></th>
				<td>
					<select id="aiwc_faq_status">
						<option value="active"><?php esc_html_e( 'Active', 'ai-website-chatbot' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'Inactive', 'ai-website-chatbot' ); ?></option>
					</select>
				</td>
			</tr>
			</tbody>
		</table>
		<p>
			<button type="button" class="button button-primary" id="aiwc_faq_save"><?php esc_html_e( 'Save FAQ', 'ai-website-chatbot' ); ?></button>
			<button type="button" class="button" id="aiwc_faq_cancel" hidden><?php esc_html_e( 'Cancel', 'ai-website-chatbot' ); ?></button>
		</p>
	</div>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Existing FAQs', 'ai-website-chatbot' ); ?></h2>
		<table class="wp-list-table widefat striped" id="aiwc_faq_table">
			<thead><tr>
				<th><?php esc_html_e( 'Question', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Category', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Status', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Order', 'ai-website-chatbot' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ai-website-chatbot' ); ?></th>
			</tr></thead>
			<tbody><tr><td colspan="5"><?php esc_html_e( 'Loading…', 'ai-website-chatbot' ); ?></td></tr></tbody>
		</table>
		<div class="aiwc-pagination" id="aiwc_faq_pagination"></div>
	</div>
</div>