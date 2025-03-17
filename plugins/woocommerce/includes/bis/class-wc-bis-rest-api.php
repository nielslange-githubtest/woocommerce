<?php
/**
 * WC_BIS_REST_API class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.7.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Endpoints.
 * Similar to Automattic\WooCommerce\RestApi\Server.php.
 *
 * @class    WC_BIS_REST_API
 * @version  1.7.0
 */
class WC_BIS_REST_API {

	/**
	 * Load required files, setups hooks and rest api fields.
	 */
	public function __construct() {
		$this->includes();
		$this->register_hooks();
	}

	/**
	 * Load REST API related files.
	 */
	private function includes() {

		// Routes Controllers.
		require_once WC_ABSPATH . 'includes/rest-api/Controllers/Version3/class-wc-bis-rest-api-back-in-stock-controller.php';
	}

	/**
	 * Sets up hooks.
	 */
	private function register_hooks() {
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ), 10 );
	}

	/**
	 * Register Back In Stock Notifications REST namespace.
	 *
	 * @param  array $namespaces List of registered namespaces.
	 * @return array
	 */
	public function register_rest_namespaces( $namespaces ) {

		// Bail out early.
		if ( isset( $namespaces['wc/v3']['back-in-stock'] ) ) {
			return $namespaces;
		}

		$namespaces['wc/v3']['back-in-stock'] = 'WC_BIS_REST_API_Back_In_Stock_Controller';

		return $namespaces;
	}
}

new WC_BIS_REST_API();
