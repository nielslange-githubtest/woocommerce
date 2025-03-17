<?php
/**
 * WC_BIS_WCS_Compatibility class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.2.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Subscriptions compatibility.
 *
 * @version  1.2.0
 */
class WC_BIS_WCS_Compatibility {

	/**
	 * Initialize integration.
	 */
	public static function init() {

		// Add Subscriptions as a supported type.
		add_filter( 'woocommerce_bis_supported_product_types', array( __CLASS__, 'add_subscriptions_product_type' ) );

		add_filter( 'woocommerce_get_stock_html', array( __CLASS__, 'handle_display_form' ) );
	}

	/**
	 * Handle the BIS form for simple subscriptions.
	 *
	 * @param  string $availability_html Availability HTML.
	 * @return string
	 */
	public static function handle_display_form( $availability_html ) {
		global $product;

		if ( ! is_a( $product, 'WC_Product' ) || ! $product->is_type( 'subscription' ) ) {
			return $availability_html;
		}

		ob_start();
		WC_BIS()->product->display_form( $product );
		$form_html = ob_get_clean();

		return $availability_html . $form_html;
	}

	/**
	 * Include Subscription in supported product types.
	 *
	 * @param  array $types Supported product types.
	 * @return array
	 */
	public static function add_subscriptions_product_type( $types ) {
		$types[] = 'subscription';
		$types[] = 'variable-subscription';
		return $types;
	}
}

WC_BIS_WCS_Compatibility::init();
