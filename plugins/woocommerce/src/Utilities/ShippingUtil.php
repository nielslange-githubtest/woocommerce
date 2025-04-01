<?php
/**
 * ShippingUtil class file.
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Utilities;

/**
 * The ShippingUtil class provides utilities for working with shipping and shipping packages.
 */
class ShippingUtil {

	/**
	 * Get the selected shipping rates from the packages.
	 *
	 * @param array $packages The packages to get the selected shipping rates from.
	 * @return array The selected shipping rates.
	 */
	public static function get_selected_shipping_rates_from_packages( $packages ) {
		return array_filter(
			array_map(
				function ( $package_id, $package ) {
					$selected_rate = wc_get_chosen_shipping_method_for_package( $package_id, $package );
					return false !== $selected_rate && isset( $package['rates'][ $selected_rate ] ) ? $package['rates'][ $selected_rate ] : null;
				},
				array_keys( $packages ),
				array_values( $packages )
			)
		);
	}
}