<?php
/**
 * WooCommerce integration.
 *
 * @package AIWebsiteChatbot
 */

namespace AIWebsiteChatbot;

defined( 'ABSPATH' ) || exit;

/**
 * Provides a safe product lookup layer. No order or customer data is exposed.
 */
class WooCommerce_Integration {

	/**
	 * Whether integration is available and enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return aiwc_is_woocommerce_active()
			&& function_exists( 'wc_get_products' )
			&& (bool) Settings::get_setting( 'woocommerce.enabled', false );
	}

	/**
	 * Search products by title, SKU, description, categories or tags.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Maximum products.
	 * @return array<int,array>
	 */
	public static function search_products( string $query, int $limit = 5 ): array {
		if ( ! self::is_enabled() ) {
			return array();
		}

		$query = trim( $query );
		if ( '' === $query ) {
			return array();
		}

		$products = self::query_products( $query, $limit );

		if ( empty( $products ) ) {
			$products = self::query_products( $query, $limit, 'title' );
		}

		$results = array();
		foreach ( $products as $product ) {
			$formatted = self::format_product( $product );
			if ( $formatted ) {
				$results[] = $formatted;
			}
		}

		return $results;
	}

	/**
	 * Run a WooCommerce product query.
	 *
	 * @param string $query      Query text.
	 * @param int    $limit      Limit.
	 * @param string $search_type all|title.
	 * @return array<int,\WC_Product>
	 */
	private static function query_products( string $query, int $limit, string $search_type = 'all' ): array {
		$args = array(
			'status'  => 'publish',
			'limit'   => max( 1, $limit ),
			'orderby' => 'relevance',
			'order'   => 'DESC',
			'return'  => 'objects',
		);

		if ( 'title' === $search_type ) {
			$args['name'] = $query;
		} else {
			$args['s'] = $query;
		}

		$products = wc_get_products( $args );

		return is_array( $products ) ? $products : array();
	}

	/**
	 * Format a product for display/context respecting visibility settings.
	 *
	 * @param \WC_Product $product Product.
	 * @return array|null
	 */
	private static function format_product( $product ): ?array {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return null;
		}

		$show_prices = (bool) Settings::get_setting( 'woocommerce.show_prices', true );
		$show_stock  = (bool) Settings::get_setting( 'woocommerce.show_stock', true );
		$show_links  = (bool) Settings::get_setting( 'woocommerce.show_links', true );
		$show_cats   = (bool) Settings::get_setting( 'woocommerce.show_categories', true );

		$id    = (int) $product->get_id();
		$title = (string) $product->get_name();
		$url   = get_permalink( $id );
		$sku   = (string) $product->get_sku();
		$desc  = aiwc_normalize_content( (string) $product->get_short_description() );

		$context = array( $title );

		if ( $show_prices ) {
			$price = (string) $product->get_price();
			if ( '' !== $price ) {
				$context[] = sprintf( __( 'Price: %s', 'ai-website-chatbot' ), wp_strip_all_tags( wc_price( $product->get_price() ) ) );
			}
		}

		if ( $show_stock ) {
			$context[] = sprintf( __( 'Availability: %s', 'ai-website-chatbot' ), $product->is_in_stock() ? __( 'In stock', 'ai-website-chatbot' ) : __( 'Out of stock', 'ai-website-chatbot' ) );
		}

		if ( $show_cats ) {
			$terms = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$context[] = sprintf( __( 'Categories: %s', 'ai-website-chatbot' ), implode( ', ', array_map( 'sanitize_text_field', $terms ) ) );
			}
		}

		if ( '' !== $sku ) {
			$context[] = sprintf( __( 'SKU: %s', 'ai-website-chatbot' ), $sku );
		}

		if ( '' !== $desc ) {
			$context[] = $desc;
		}

		return array(
			'id'      => $id,
			'title'   => $title,
			'url'     => $show_links ? $url : '',
			'context' => implode( "\n", $context ),
		);
	}

	/**
	 * Product context for prompt building.
	 *
	 * @param string $query Query.
	 * @param int    $limit Limit.
	 * @return array
	 */
	public static function search_for_context( string $query, int $limit = 4 ): array {
		return self::search_products( $query, $limit );
	}
}