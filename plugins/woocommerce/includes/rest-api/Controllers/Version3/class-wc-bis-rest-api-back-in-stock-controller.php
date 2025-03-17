<?php
/**
 * WC_BIS_REST_API_Back_In_Stock_Controller class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    9.9.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Back In Stock Notifications class.
 *
 * @class   WC_BIS_REST_API_Back_In_Stock_Controller
 * @extends WC_REST_Controller
 * @version  9.9.0
 */
class WC_BIS_REST_API_Back_In_Stock_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'back-in-stock';

	/**
	 * Register the routes for Back In Stock Notifications.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/notifications/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'deactivate_notifications' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_deactivation_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/notifications/force-send',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'force_send_notifications' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_force_send_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Deactivate notifications.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * 
	 * @throws Exception If no notifications are found to deactivate.
	 * 
	 * @return WP_REST_Response|WP_Error
	 */
	public function deactivate_notifications( $request ) {

		try {

			$params        = $request->get_params();
			$endpoint_args = $this->get_deactivation_collection_params();
			foreach ( $params as $key => $param ) {
				if ( empty( $endpoint_args[ $key ] ) ) {
					unset( $params[ $key ] );
				}
			}

			if ( ! class_exists( 'WC_BIS_Admin' ) ) {
				require_once WC_ABSPATH . 'includes/admin/class-wc-bis-admin.php';
			}

			$updated = WC_BIS_Admin::handle_bulk_admin_deactivation( $params['productId'] );

			if ( 0 === $updated ) {
				throw new Exception( __( 'No notifications found to deactivate.', 'woocommerce' ), 404 );
			}

			$notice = sprintf(
				// translators: %1$s: the number of deactivated notifications.
				_n(
					'%1$s notification deactivated.',
					'%1$s notifications deactivated.',
					$updated,
					'woocommerce'
				),
				number_format_i18n( $updated )
			);

			$data     = array(
				'status' => 'success',
				'notice' => $notice,
			);
			$response = rest_ensure_response( $data );

			return $response;

		} catch ( Exception $e ) {
			$data = array( 'status' => $e->getCode() ? $e->getCode() : 500 );
			return new WP_Error( 'woocommerce_bis_rest_api_notifications_not_deactivated', $e->getMessage(), $data );
		}
	}

	/**
	 * Force-Send notifications.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * 
	 * @throws Exception If no product IDs are found to force-send notifications.
	 * @return WP_REST_Response|WP_Error
	 */
	public function force_send_notifications( $request ) {

		try {

			$params        = $request->get_params();
			$endpoint_args = $this->get_force_send_collection_params();
			foreach ( $params as $key => $param ) {
				if ( empty( $endpoint_args[ $key ] ) ) {
					unset( $params[ $key ] );
				}
			}

			if ( empty( $params['productIds'] ) ) {
				throw new Exception( __( 'No product IDs found to force-send notifications.', 'woocommerce' ), 404 );
			}

			if ( ! class_exists( 'WC_BIS_Admin_Notices' ) ) {
				require_once WC_ABSPATH . 'includes/admin/class-wc-bis-admin-notices.php';
			}

			WC_BIS_Sync_Tasks::handle_instock_products( $params['productIds'], true );

			$notices = WC_BIS_Admin_Notices::$meta_box_notices;
			// Reset notices.
			WC_BIS_Admin_Notices::$meta_box_notices = array();
			$data                                   = array(
				'status'  => 'success',
				'notices' => class_exists( 'WC_BIS_Product_Editor_Compatibility' ) ? WC_BIS_Product_Editor_Compatibility::rest_prepare_flash_notices( $notices ) : $notices,
			);
			$response                               = rest_ensure_response( $data );

			return $response;

		} catch ( Exception $e ) {
			$data = array( 'status' => $e->getCode() ? $e->getCode() : 500 );
			return new WP_Error( 'woocommerce_bis_rest_api_notifications_not_deactivated', $e->getMessage(), $data );
		}
	}

	/**
	 * Check if a given request has access to deactivate notifications.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get any query params needed for deactivating notifications.
	 *
	 * @return array
	 */
	public function get_deactivation_collection_params() {
		$params              = array();
		$params['productId'] = array(
			'description'       => __( 'The product ID to deactivate notifications.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		return $params;
	}

	/**
	 * Get any query params needed for deactivating notifications.
	 *
	 * @return array
	 */
	public function get_force_send_collection_params() {
		$params               = array();
		$params['productIds'] = array(
			'default'           => array(),
			'description'       => __( 'The product IDs to force-send notifications. Ensure to specify variation IDs.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'sanitize_callback' => 'wp_parse_list',
		);
		return $params;
	}
}
