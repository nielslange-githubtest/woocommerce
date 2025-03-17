<?php
/**
 * WC_BIS_Bundles_Compatibility class
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
 * WooCommerce Product Bundles compatibility.
 *
 * @version  1.0.0
 */
class WC_BIS_Bundles_Compatibility {

	/**
	 * Initialize integration.
	 */
	public static function init() {

		// Add Bundles as a supported type.
		add_filter( 'woocommerce_bis_supported_product_types', array( __CLASS__, 'add_bundle_product_type' ) );

		// Bypass regular sync.
		add_filter( 'woocommerce_bis_validate_product_sync', array( __CLASS__, 'bypass_native_sync' ), 10, 2 );

		// Trigger sync.
		add_action( 'woocommerce_product_object_updated_props', array( __CLASS__, 'bundle_stock_changed' ), 10, 2 );

		// Handle reactivation.
		add_action( 'woocommerce_bis_notification_reactivate', array( __CLASS__, 'notification_reactivate' ) );
		add_filter( 'woocommerce_bis_notification_reactivation_args', array( __CLASS__, 'handle_reactivation_args' ), 10, 2 );

		// Display form.
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'handle_display_form' ) );
	}

	/**
	 * Handle form display for Bundles.
	 *
	 * @return void
	 */
	public static function handle_display_form() {
		global $product;
		if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'bundle' ) ) {
			WC_BIS()->product->display_form( $product );
		}
	}

	/**
	 * Reactivate notification.
	 *
	 * @param  object $notification Notification.
	 * @return void
	 */
	public static function notification_reactivate( $notification ) {
		$product = $notification->get_product();
		if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'bundle' ) && 'outofstock' === $product->get_bundled_items_stock_status() ) {
			$notification->set_subscribe_date( time() );
		}
	}

	/**
	 * Handle reactivation args.
	 *
	 * @param  array                    $args Array of arguments.
	 * @param  WC_BIS_Notification_Data $notification Notification data.
	 * @return array
	 */
	public static function handle_reactivation_args( $args, $notification ) {
		$product = $notification->get_product();
		if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'bundle' ) && 'outofstock' === $product->get_bundled_items_stock_status() ) {
			$args['subscribe_date'] = time();
		}

		return $args;
	}

	/**
	 * Include Bundles in supported product types.
	 *
	 * @param  array $types Supported product types.
	 * @return array
	 */
	public static function add_bundle_product_type( $types ) {
		$types[] = 'bundle';
		return $types;
	}

	/**
	 * Bypass native BIS sync action for bundle types.
	 *
	 * @param  bool       $valid  Whether the product should be synced.
	 * @param  WC_Product $product Product.
	 * @return bool
	 */
	public static function bypass_native_sync( $valid, $product ) {

		if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'bundle' ) ) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Sync Product Bundle's stock change.
	 *
	 * @param  WC_Product $product Product.
	 * @param  array      $updated_props Updated props.
	 * @return void
	 */
	public static function bundle_stock_changed( $product, $updated_props = array() ) {

		if ( empty( $updated_props ) || ! is_array( $updated_props ) ) {
			return;
		}

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( ! $product->is_type( 'bundle' ) ) {
			return;
		}

		if ( 'publish' !== get_post_status( $product->get_id() ) ) {
			return;
		}

		if ( in_array( 'bundled_items_stock_status', $updated_props, true ) ) {

			$stock_status = $product->get_bundled_items_stock_status( 'edit' );
			self::add_bundle( $product, $stock_status );
		}

		if ( in_array( 'bundle_stock_quantity', $updated_props, true ) ) {

			$min_stock_threshold = wc_bis_get_stock_threshold();
			if ( 0 === $min_stock_threshold ) {
				// If threshold is set to zero, no need to check for stock qty updates or set a meta record.
				return;
			}

			// Get current value.
			$stock        = $product->get_bundle_stock_quantity();
			$stock_status = 0 === $stock || $stock < 0 ? 'outofstock' : 'instock';
			// Get previous value.
			$previous_stock = get_post_meta( $product->get_id(), 'wc_bis_previous_stock', true );
			$meta_exists    = '' !== $previous_stock;
			$previous_stock = (int) $previous_stock;
			$sync_product   = false;

			if ( $stock >= $min_stock_threshold && $previous_stock < $min_stock_threshold && $meta_exists ) {
				// Increased stock more than threshold.
				$sync_product = true;
			}

			// Make sure script runs at new products/newly managed stock products.
			if ( ! $meta_exists ) {
				$sync_product = true;
			}

			if ( $sync_product ) {
				self::add_bundle( $product, $stock_status );
			}

			// Keep track of previous stock.
			update_post_meta( $product->get_id(), 'wc_bis_previous_stock', $stock );
		}
	}

	/**
	 * Add a Bundle to the sync queue.
	 *
	 * @param  mixed  $product Product.
	 * @param  string $stock_status Stock status.
	 * @return bool
	 */
	public static function add_bundle( $product, $stock_status ) {

		$added               = false;
		$min_stock_threshold = wc_bis_get_stock_threshold();

		if ( 'outofstock' === $stock_status ) {

			$added = WC_BIS()->sync->add_to_queue( $product->get_id(), 'outofstock' );

		} elseif ( $product->get_bundle_stock_quantity() >= $min_stock_threshold ) { // Sanity check for newcomers without a previous meta.
			$added = WC_BIS()->sync->add_to_queue( $product->get_id(), 'instock' );
		}

		return $added;
	}
}

WC_BIS_Bundles_Compatibility::init();
