<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Cart\Utilities;

use WC_Product;

/**
 * Cart utilities.
 *
 * @since 8.0.0
 */
class CartUtils {
	/**
	 * Generate a cart ID based on product details.
	 *
	 * @param int   $product_id   ID of the product.
	 * @param int   $variation_id ID of the variation.
	 * @param array $variation    Attribute values.
	 * @param array $cart_item_data Extra cart item data.
	 * @return string Generated cart ID (MD5 hash).
	 */
	public static function generate_cart_id( $product_id, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		$id_parts = array( $product_id );

		if ( $variation_id && 0 !== $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( is_array( $variation ) && ! empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $key => $value ) {
				$variation_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $variation_key;
		}

		if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) {
			$cart_item_data_key = '';
			foreach ( $cart_item_data as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_item_data_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $cart_item_data_key;
		}

		$cart_id = md5( implode( '_', $id_parts ) );

		/**
		 * Filter to adjust the cart item ID.
		 *
		 * @since 2.0.0
		 * @param string $cart_id      Generated cart ID (MD5 hash).
		 * @param int    $product_id   ID of the product.
		 * @param int    $variation_id ID of the variation.
		 * @param array  $variation    Attribute values.
		 * @param array  $cart_item_data Extra cart item data.
		 */
		return apply_filters( 'woocommerce_cart_id', $cart_id, $product_id, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Generate a key from variation data.
	 *
	 * @since 8.0.0
	 * @param array $variation Variation data.
	 * @return string
	 */
	private static function generate_variation_key( array $variation ): string {
		return implode(
			'',
			array_map(
				function ( $key, $value ) {
					return trim( $key ) . trim( $value );
				},
				array_keys( $variation ),
				$variation
			)
		);
	}

	/**
	 * Generate a key from cart item data.
	 *
	 * @since 8.0.0
	 * @param array $cart_item_data Cart item data.
	 * @return string
	 */
	private static function generate_cart_item_data_key( array $cart_item_data ): string {
		return implode(
			'',
			array_map(
				function ( $key, $value ) {
					return trim( $key ) . trim( is_array( $value ) || is_object( $value ) ? http_build_query( $value ) : $value );
				},
				array_keys( $cart_item_data ),
				$cart_item_data
			)
		);
	}

	/**
	 * Gets a hash of important product data that when changed should cause cart items to be invalidated.
	 *
	 * @since 8.0.0
	 * @param WC_Product $product Product object.
	 * @return string MD5 Hash of product data.
	 */
	public static function get_cart_item_data_hash( WC_Product $product ): string {
		return md5(
			wp_json_encode(
				/**
				 * Filter the product data used to generate the cart item hash.
				 *
				 * @since 8.0.0
				 * @param array      $data    Product data.
				 * @param WC_Product $product Product object.
				 */
				apply_filters(
					'woocommerce_cart_item_data_to_validate',
					array(
						'type'       => $product->get_type(),
						'attributes' => 'variation' === $product->get_type() ? $product->get_variation_attributes() : '',
					),
					$product
				)
			)
		);
	}

	/**
	 * Format a stock quantity for display.
	 *
	 * @since 8.0.0
	 * @param int|float  $stock_quantity Stock quantity.
	 * @param WC_Product $product Product object for context.
	 * @return string Formatted stock quantity.
	 */
	public static function format_stock_quantity_for_display( $stock_quantity, WC_Product $product ): string {
		/**
		 * Filter the formatted stock quantity.
		 *
		 * @since 8.0.0
		 * @param mixed      $stock_quantity Formatted stock quantity.
		 * @param int|float  $raw_quantity   Raw stock quantity.
		 * @param WC_Product $product        Product object.
		 */
		return (string) apply_filters(
			'woocommerce_format_stock_quantity',
			$stock_quantity,
			$stock_quantity,
			$product
		);
	}
}
