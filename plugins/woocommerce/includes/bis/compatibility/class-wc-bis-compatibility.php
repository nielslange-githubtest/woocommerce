<?php
/**
 * WC_BIS_Compatibility class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles compatibility with other WC extensions.
 *
 * @class    WC_BIS_Compatibility
 * @version  x.x.x
 */
class WC_BIS_Compatibility {

	/**
	 * Min required plugin versions to check.
	 *
	 * @var array
	 */
	private static $required = array(
		'wc_bundles' => '6.5.1',
	);

	/**
	 * Setup compatibility class.
	 */
	public static function init() {
		// Initialize.
		self::load_modules();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	protected static function load_modules() {

		if ( is_admin() ) {
			// Check plugin min versions.
			add_action( 'admin_init', array( __CLASS__, 'add_compatibility_notices' ) );
		}

		// Include core compatibility class.
		self::core_includes();

		// Load modules.
		add_action( 'plugins_loaded', array( __CLASS__, 'module_includes' ), 100 );

		// Prevent initialization of deprecated mini-extensions usually loaded on 'plugins_loaded' -- 10.
		self::unload_modules();
	}

	/**
	 * Core compatibility functions.
	 *
	 * @return void
	 */
	public static function core_includes() {
		require_once WC_ABSPATH . 'includes/bis/compatibility/core/class-wc-bis-core-compatibility.php';
	}

	/**
	 * Prevent deprecated mini-extensions from initializing.
	 *
	 * @return void
	 */
	protected static function unload_modules() {
		// Silence.
	}

	/**
	 * Load compatibility classes.
	 *
	 * @return void
	 */
	public static function module_includes() {

		$module_paths = array();

		// WC Product Bundles support.
		if ( class_exists( 'WC_Bundles' ) && defined( 'WC_PB_VERSION' ) && version_compare( WC_PB_VERSION, self::$required['wc_bundles'] ) >= 0 ) {
			$module_paths['wc_bundles'] = 'modules/class-wc-bis-bundles-compatibility.php';
		}

		// WC Pre-Orders support.
		if ( class_exists( 'WC_Pre_Orders' ) && defined( 'WC_VERSION' ) ) {
			$module_paths['wc_pre_orders'] = 'modules/class-wc-bis-pre-orders-compatibility.php';
		}

		// WCS support.
		if ( class_exists( 'WC_Subscriptions' ) || class_exists( 'WC_Subscriptions_Core_Plugin' ) ) {
			$module_paths['wc_subscriptions'] = 'modules/class-wc-bis-wcs-compatibility.php';
		}

		/**
		 * 'woocommerce_bis_compatibility_modules' filter.
		 *
		 * Use this to filter the required compatibility modules.
		 *
		 * @since  1.0.0
		 * @param  array $module_paths
		 */
		$module_paths = apply_filters( 'woocommerce_bis_compatibility_modules', $module_paths );

		foreach ( $module_paths as $path ) {
			require_once $path;
		}
	}

	/**
	 * Checks versions of compatible/integrated/deprecated extensions.
	 *
	 * @return void
	 */
	public static function add_compatibility_notices() {

		// WC Product Bundles version check.
		if ( class_exists( 'WC_Bundles' ) ) {

			$required_version = self::$required['wc_bundles'];

			if ( ! defined( 'WC_PB_VERSION' ) || version_compare( WC_PB_VERSION, $required_version ) < 0 ) {

				$extension      = __( 'Product Bundles', 'woocommerce' );
				$extension_full = __( 'WooCommerce Product Bundles', 'woocommerce' );
				$extension_url  = 'https://woocommerce.com/products/product-bundles/';
				/* translators: %1$s extension name, %2$s extension url, %3$s extension full name */
				$notice = sprintf( __( 'The installed version of <strong>%1$s</strong> is not supported by <strong>WooCommerce Back In Stock Notifications</strong>. Please update <a href="%2$s" target="_blank">%3$s</a> to version <strong>%4$s</strong> or higher.', 'woocommerce' ), $extension, $extension_url, $extension_full, $required_version );

				WC_BIS_Admin_Notices::add_dismissible_notice(
					$notice,
					array(
						'dismiss_class' => 'wc_bundles_lt_' . $required_version,
						'type'          => 'warning',
					)
				);
			}
		}
	}
}

WC_BIS_Compatibility::init();
