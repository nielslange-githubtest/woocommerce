<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\AddressAutocomplete;

if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
	/**
	 * Register a checkout field.
	 *
	 * @param array $options Field arguments. See CheckoutFields::register_checkout_field() for details.
	 * @throws \Exception If field registration fails.
	 */
	function woocommerce_register_additional_checkout_field( $options ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore

		// Check if `woocommerce_blocks_loaded` ran. If not then the CheckoutFields class will not be available yet.
		// In that case, re-hook `woocommerce_blocks_loaded` and try running this again.
		$woocommerce_blocks_loaded_ran = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $woocommerce_blocks_loaded_ran ) {
			add_action(
				'woocommerce_blocks_loaded',
				function () use ( $options ) {
					woocommerce_register_additional_checkout_field( $options );
				}
			);
			return;
		}
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->register_checkout_field( $options );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_attr( $result->get_error_message() ) );
		}
	}
}

if ( ! function_exists( '__experimental_woocommerce_blocks_register_checkout_field' ) ) {

	/**
	 * Register a checkout field.
	 *
	 * @param array $options Field arguments. See CheckoutFields::register_checkout_field() for details.
	 * @throws \Exception If field registration fails.
	 * @deprecated 5.6.0 Use woocommerce_register_additional_checkout_field() instead.
	 */
	function __experimental_woocommerce_blocks_register_checkout_field( $options ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		wc_deprecated_function( __FUNCTION__, '8.9.0', 'woocommerce_register_additional_checkout_field' );
		woocommerce_register_additional_checkout_field( $options );
	}
}

if ( ! function_exists( '__internal_woocommerce_blocks_deregister_checkout_field' ) ) {
	/**
	 * Deregister a checkout field.
	 *
	 * @param string $field_id Field ID.
	 * @throws \Exception If field deregistration fails.
	 * @internal
	 */
	function __internal_woocommerce_blocks_deregister_checkout_field( $field_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->deregister_checkout_field( $field_id );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_attr( $result->get_error_message() ) );
		}
	}
}

if ( ! function_exists( '__experimental_woocommerce_register_address_autocomplete_provider' ) ) {
	/**
	 * Register an address autocomplete provider.
	 *
	 * @param string $provider_id The unique identifier for the provider.
	 * @param string $name        The human-readable name of the provider.
	 * @return bool True if registration was successful, false otherwise.
	 */
	function __experimental_woocommerce_register_address_autocomplete_provider( string $provider_id, string $name ): bool {
		// Check if `woocommerce_blocks_loaded` ran. If not then the AddressAutocomplete class will not be available yet.
		$woocommerce_blocks_loaded_ran = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $woocommerce_blocks_loaded_ran ) {
			add_action(
				'woocommerce_blocks_loaded',
				function () use ( $provider_id, $name ) {
					__experimental_woocommerce_register_address_autocomplete_provider( $provider_id, $name );
				}
			);
			return false;
		}

		try {
			$address_autocomplete = Package::container()->get( AddressAutocomplete::class );
			return $address_autocomplete->register_provider( $provider_id, $name );
		} catch ( \Exception $e ) {
			return false;
		}
	}
}

if ( ! function_exists( '__experimental_woocommerce_deregister_address_autocomplete_provider' ) ) {
	/**
	 * Deregister an address autocomplete provider.
	 *
	 * @param string $provider_id Provider ID.
	 * @internal
	 */
	function __experimental_woocommerce_deregister_address_autocomplete_provider( string $provider_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		$address_autocomplete = Package::container()->get( AddressAutocomplete::class );
		$address_autocomplete->deregister_provider( $provider_id );
	}
}
