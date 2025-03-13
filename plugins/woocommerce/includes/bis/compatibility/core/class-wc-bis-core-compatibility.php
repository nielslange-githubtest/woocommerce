<?php
/**
 * WC_BIS_Core_Compatibility class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions related to core back-compatibility.
 *
 * @class    WC_BIS_Core_Compatibility
 * @version  2.0.0
 */
class WC_BIS_Core_Compatibility {

	/**
	 * Cache 'gte' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gte = array();

	/**
	 * Cache 'gt' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gt = array();

	/**
	 * Cache 'gt' comparison results for WP version.
	 *
	 * @var array
	 */
	private static $is_wp_version_gt = array();

	/**
	 * Cache 'gte' comparison results for WP version.
	 *
	 * @var array
	 */
	private static $is_wp_version_gte = array();

	/**
	 * Cache wc admin status result.
	 *
	 * @var bool
	 */
	private static $is_wc_admin_enabled = null;

	/**
	 * Initialization and hooks.
	 */
	public static function init() {
		// ...
	}

	/*
	|--------------------------------------------------------------------------
	| WC version handling.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @return string
	 */
	public static function get_wc_version() {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::get_wc_version', 9.8, 'Constants::get_constant( \'WC_VERSION\' )' );

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wc_version_gte( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wc_version_gte', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wc_version_gte[ $version ] ) ) {
			self::$is_wc_version_gte[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}
		return self::$is_wc_version_gte[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wc_version_gt( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wc_version_gt', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is lower than or equal $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wc_version_lte( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wc_version_lte', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '<=' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WooCommerce is lower than $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wc_version_lt( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wc_version_lt', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '<' );
		}
		return self::$is_wc_version_gt[ $version ];
	}

	/*
	|--------------------------------------------------------------------------
	| WP version handling.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns true if the installed version of WordPress is greater than or equal to $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wp_version_gt( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wp_version_gt', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wp_version_gt[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gt[ $version ] = $wp_version && version_compare( WC_BIS()->get_plugin_version( true, $wp_version ), $version, '>' );
		}
		return self::$is_wp_version_gt[ $version ];
	}

	/**
	 * Returns true if the installed version of WordPress is greater than or equal to $version.
	 *
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wp_version_gte( $version ) {
		wc_deprecated_function( 'WC_BIS_Core_Compatibility::is_wp_version_gte', 9.8, 'version_compare' );

		if ( ! isset( self::$is_wp_version_gte[ $version ] ) ) {
			global $wp_version;
			self::$is_wp_version_gte[ $version ] = $wp_version && version_compare( WC_BIS()->get_plugin_version( true, $wp_version ), $version, '>=' );
		}
		return self::$is_wp_version_gte[ $version ];
	}

	/**
	 * Returns true if site is using block theme.
	 *
	 * @since  1.6.0
	 *
	 * @return boolean
	 */
	public static function wc_current_theme_is_fse_theme() {
		wc_deprecated_function( __METHOD__, 9.9, 'wc_current_theme_is_fse_theme' );
		return function_exists( 'wc_current_theme_is_fse_theme' ) ? wc_current_theme_is_fse_theme() : false;
	}
}

WC_BIS_Core_Compatibility::init();
