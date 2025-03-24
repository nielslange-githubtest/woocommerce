<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Internal\Logging\RemoteLogger;
use InvalidArgumentException;

/**
 * Manages the registration of interactivity settings for WooCommerce blocks.
 * Initialization only happens on the first call to init().
 */
class BlocksInteractivityConfig {

	/**
	 * The namespace for the settings.
	 *
	 * @var string
	 */
	private static $settings_namespace = 'woocommerce/settings';

	/**
	 * Whether the core settings have been registered.
	 *
	 * @var boolean
	 */
	private static $core_settings_registered = false;

	/**
	 * Initialize the core settings.
	 */
	public static function init() {
		if ( self::$core_settings_registered ) {
			return;
		}

		self::$core_settings_registered = true;

		self::add( self::get_currency_data() );
		self::add( self::get_locale_data() );
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	protected static function get_currency_data() {
		$currency = get_woocommerce_currency();

		return [
			'currency' => [
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			],
		];
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	protected static function get_locale_data() {
		global $wp_locale;

		return [
			'locale' => [
				'siteLocale'    => get_locale(),
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			],
		];
	}

	/**
	 * Interface for adding block settings to wp_interactivity_config. New settings will be merged with existing settings.
	 *
	 * @param array $data The data to add to the settings.
	 */
	public static function add( $data ) {
		if ( ! self::$core_settings_registered ) {
			self::init();
		}

		wp_interactivity_config( self::$settings_namespace, $data );
	}
}
