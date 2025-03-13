<?php
/**
 * WC_BIS_Sync class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync Stock Controller.
 *
 * @class    WC_BIS_Sync
 * @version  x.x.x
 */
class WC_BIS_Sync {

	/**
	 * Mark sync.
	 *
	 * @var boolean
	 */
	private $sync_needed = false;

	/**
	 * Product ids that need syncing at shutdown.
	 *
	 * @var array
	 */
	private $queue = array();

	/**
	 * Init.
	 */
	public function __construct() {

		// Stock status changes.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'product_stock_status_changed' ), 100, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'product_stock_status_changed' ), 100, 3 );

		// Stock amount changes.
		add_action( 'woocommerce_product_set_stock', array( $this, 'product_stock_changed' ), 100 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'product_stock_changed' ), 100 );

		// Save variable product and get the synced variations statuses.
		add_action( 'shutdown', array( $this, 'sync' ), 100 );
	}

	/**
	 * Sync stock.
	 *
	 * @return void
	 */
	public function sync() {

		if ( ! $this->sync_needed ) {
			return;
		}

		if ( wc_bis_debug_enabled() ) {
			wc_get_logger()->info(
				'Sync stock queue activated for back in stock notifications.',
				array(
					'source'      => 'wc_bis_sync_logs',
					'product_ids' => $this->queue,
				)
			);
		}

		if ( ! empty( $this->queue['outofstock'] ) ) {
			do_action( 'woocommerce_bis_sync_handle_outofstock_products', $this->queue['outofstock'] );
		}

		if ( ! empty( $this->queue['instock'] ) ) {
			do_action( 'woocommerce_bis_sync_handle_instock_products', $this->queue['instock'] );
		}

		$this->queue       = array();
		$this->sync_needed = false;
	}

	/**
	 * Handle stock status changes.
	 *
	 * @param  mixed  $product_id
	 * @param  string $stock_status
	 * @param  mixed  $product (Optional)
	 * @return void
	 */
	public function product_stock_status_changed( $product_id, $stock_status, $product = null ) {

		if ( is_null( $product ) ) {
			$product = wc_get_product( $product_id );
		}

		if ( ! $this->validate_product( $product, 'stock_status' ) ) {
			return;
		}

		$added = $this->add_product( $product, $stock_status );

		// Sync variations.
		if ( $added && $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations( 'objects' );

			foreach ( $variations as $variation ) {

				$variation_stock_status = $variation->is_in_stock() ? 'instock' : 'outofstock';

				// If variation has no manage stock and it's different than the parent's -- it's gonna change later.
				if ( 'parent' === $variation->get_manage_stock() && $stock_status !== $variation_stock_status ) {
					$this->add_product( $variation, $stock_status );
				}
			}
		}

		/**
		 * Hook: woocommerce_bis_before_stock_change
		 *
		 * @param WC_Product  $product
		 */
		do_action( 'woocommerce_bis_before_stock_status_change', $product );
	}

	/**
	 * Handle stock changes.
	 *
	 * @param  WC_Product $product
	 * @return void
	 */
	public function product_stock_changed( $product ) {

		$min_stock_threshold = wc_bis_get_stock_threshold();
		if ( 0 === $min_stock_threshold ) {
			// If threshold is set to zero, no need to check for stock qty updates or set a meta record.
			return;
		}

		if ( ! $this->validate_product( $product, 'stock' ) ) {
			return;
		}

		// Get current value.
		$stock        = $product->get_stock_quantity();
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

		// Add Product.
		if ( $sync_product ) {

			$this->add_product( $product, $stock_status );

			// Sync variations.
			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations( 'objects' );

				foreach ( $variations as $variation ) {
					if ( 'parent' === $variation->get_manage_stock() ) {
						$this->add_product( $variation, $stock_status );
					}
				}
			}
		}

		// Keep track of previous stock.
		update_post_meta( $product->get_id(), 'wc_bis_previous_stock', $stock );

		/**
		 * Hook: woocommerce_bis_before_stock_change
		 *
		 * @param WC_Product  $product
		 */
		do_action( 'woocommerce_bis_before_stock_change', $product );
	}

	/**
	 * Add a product id to the sync queue.
	 *
	 * @param  mixed  $product
	 * @param  string $stock_status
	 * @return bool
	 */
	public function add_product( $product, $stock_status ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( absint( $product ) );
		}

		if ( ! $product || ! ( is_a( $product, 'WC_Product' ) ) ) {
			return false;
		}

		$added               = false;
		$min_stock_threshold = wc_bis_get_stock_threshold();

		if ( 'outofstock' === $stock_status ) {

			$added = $this->add_to_queue( $product->get_id(), 'outofstock' );

		} elseif ( true === $product->get_manage_stock() ) {

				// Sanity check for newcomers without a previous meta.
			if ( $product->get_stock_quantity() >= $min_stock_threshold ) {
				$added = $this->add_to_queue( $product->get_id(), 'instock' );
			}
		} else {
				$added = $this->add_to_queue( $product->get_id(), 'instock' );
		}

		return $added;
	}

	/**
	 * Add a product id to the sync queue.
	 *
	 * @param  int    $product_id
	 * @param  string $group
	 * @return bool
	 */
	public function add_to_queue( $product_id, $group ) {

		// Sanity check the group.
		if ( ! in_array( $group, array( 'instock', 'outofstock' ) ) ) {
			return false;
		}

		// Init data if needed.
		if ( ! is_array( $this->queue ) ) {
			$this->queue = array();
		}
		if ( ! isset( $this->queue[ $group ] ) ) {
			$this->queue[ $group ] = array();
		}

		// Already added.
		if ( in_array( $product_id, $this->queue[ $group ] ) ) {
			return true;
		}

		// Add product id.
		$this->queue[ $group ][] = $product_id;

		// Enable sync at the end.
		$this->sync_needed = true;

		return true;
	}

	/**
	 * Validate product to be synced.
	 *
	 * @param  WC_Product $product
	 * @param  string     $context
	 * @return bool
	 */
	public function validate_product( $product, $context = '' ) {
		$valid = false;

		if ( $product->is_type( wc_bis_get_supported_types() ) ) {
			$valid = true;
		}

		return (bool) apply_filters( 'woocommerce_bis_validate_product_sync', $valid, $product );
	}
}
