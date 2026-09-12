<?php
/**
 * WooCommerce view.
 *
 * @package AIWebsiteChatbot
 */

defined( 'ABSPATH' ) || exit;

$wc_active = function_exists( 'aiwc_is_woocommerce_active' ) ? \aiwc_is_woocommerce_active() : false;
$s         = function ( $key, $default = '' ) {
	return \aiwc_get_setting( $key, $default );
};
?>
<div class="wrap aiwc-wrap">
	<h1><?php esc_html_e( 'WooCommerce Integration', 'ai-website-chatbot' ); ?></h1>

	<?php if ( ! $wc_active ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'WooCommerce is not active on this site. Product answers will be disabled until it is installed.', 'ai-website-chatbot' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'aiwc_settings_group' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable WooCommerce answers', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[woocommerce][enabled]" value="0" /><label><input type="checkbox" name="aiwc_settings[woocommerce][enabled]" value="1" <?php checked( (bool) $s( 'woocommerce.enabled', false ) ); ?> /> <?php esc_html_e( 'Let the chatbot answer product questions using WooCommerce data.', 'ai-website-chatbot' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show prices', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[woocommerce][show_prices]" value="0" /><label><input type="checkbox" name="aiwc_settings[woocommerce][show_prices]" value="1" <?php checked( (bool) $s( 'woocommerce.show_prices', true ) ); ?> /></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show stock status', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[woocommerce][show_stock]" value="0" /><label><input type="checkbox" name="aiwc_settings[woocommerce][show_stock]" value="1" <?php checked( (bool) $s( 'woocommerce.show_stock', true ) ); ?> /></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show product links', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[woocommerce][show_links]" value="0" /><label><input type="checkbox" name="aiwc_settings[woocommerce][show_links]" value="1" <?php checked( (bool) $s( 'woocommerce.show_links', true ) ); ?> /></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Show categories', 'ai-website-chatbot' ); ?></th>
				<td><input type="hidden" name="aiwc_settings[woocommerce][show_categories]" value="0" /><label><input type="checkbox" name="aiwc_settings[woocommerce][show_categories]" value="1" <?php checked( (bool) $s( 'woocommerce.show_categories', true ) ); ?> /></label></td>
			</tr>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>

	<div class="aiwc-panel">
		<h2><?php esc_html_e( 'How it works', 'ai-website-chatbot' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Products are included in the knowledge index during indexing. For product questions the chatbot additionally searches products live by title, SKU, description, categories and tags. It never returns prices, stock or specifications that are not present in your store data — and never exposes order or customer information.', 'ai-website-chatbot' ); ?></p>
	</div>
</div>