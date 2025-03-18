<?php
/**
 * Back In Stock Notifications test helper
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    3.0.0
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\BackInStockNotifications;

/**
 * WC_BIS_Test_Helper class.
 *
 * @version 3.0.0
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_BIS_Test_Helper {

	/**
	 * Enable the BIS feature flag for tests.
	 *
	 * @return void
	 */
	public static function enable_feature() {
		update_option( BackInStockNotifications::$enable_option_name, 'yes' );
	}

	/**
	 * Disable the BIS feature flag for tests.
	 *
	 * @return void
	 */
	public static function disable_feature() {
		update_option( BackInStockNotifications::$enable_option_name, 'no' );
	}

	/**
	 * Reset the BIS feature flag to its default state.
	 *
	 * @return void
	 */
	public static function reset_feature() {
		delete_option( BackInStockNotifications::$enable_option_name );
	}
}
