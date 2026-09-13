<?php
/**
 * Knowledge Base view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

$available_types = \AIWebsiteChatbot\Content_Indexer::available_post_types();
$counts          = \AIWebsiteChatbot\Content_Indexer::candidate_counts_by_type();

$saved_types = array_unique(
	array_map(
		'sanitize_key',
		(array) \aiwc_get_setting( 'knowledge.content_types', array( 'page', 'post' ) )
	)
);
if ( \aiwc_is_woocommerce_active() ) {
	$saved_types[] = 'product';
}
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'Knowledge Base', 'ai-website-chatbot' ); ?></h1>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'Content to index', 'ai-website-chatbot' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Choose which post types are indexed: pages, posts and any custom post types. Only published, non-password-protected entries are included.', 'ai-website-chatbot' ); ?>
		</p>
		<div class="aiwc-type-grid" id="aiwc_content_types">
			<?php foreach ( $available_types as $name => $object ) : ?>
				<?php $entry_count = (int) ( $counts[ $name ] ?? 0 ); ?>
				<label class="aiwc-type-item">
					<input type="checkbox" data-type="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $saved_types, true ) ); ?> />
					<span class="aiwc-type-label"><?php echo esc_html( $object->labels->name ); ?></span>
					<span class="aiwc-type-count"><?php echo esc_html( sprintf( _n( '%d entry', '%d entries', $entry_count, 'ai-website-chatbot' ), $entry_count ) ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button button-primary" id="aiwc_types_save" disabled><?php esc_html_e( 'Save post types', 'ai-website-chatbot' ); ?></button>
			<span class="description" id="aiwc_types_hint"><?php esc_html_e( 'Changes are applied the next time the website is indexed. Re-index to update the existing entries.', 'ai-website-chatbot' ); ?></span>
		</p>
	</div>

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
				<option value="faq"><?php esc_html_e( 'FAQ', 'ai-website-chatbot' ); ?></option>
				<?php foreach ( $available_types as $name => $object ) : ?>
					<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $object->labels->name ); ?></option>
				<?php endforeach; ?>
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