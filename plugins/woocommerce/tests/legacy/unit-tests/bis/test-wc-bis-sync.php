<?php
/**
 * WC_BIS_Sync_Tests test cases
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    3.0.0
 */

/**
 * Test cases for the 'WC_BIS_Sync_Tests' class.
 *
 * @since 3.0.0
 */
class WC_BIS_Sync_Tests extends WC_BIS_Test_Case {

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		WC_Emails::instance();
		WC_Emails::init_transactional_emails();
		WC_BIS()->emails->email_classes( array() );
		parent::setUp();
	}

	/**
	 * Test the sync function for out-of-stock product transition.
	 */
	public function test_product_out_of_stock_transition() {

		// Test data.
		$product      = WC_Helper_Product::create_simple_product();
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id() );

		// Check initial state.
		$this->assertEquals( 0, $notification->get_subscribe_date() );

		// Make the product out of stock.
		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Ensure waiting time is set.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );
	}

	/**
	 * Test the sync function for product in stock transition.
	 */
	public function test_product_in_stock_transition() {

		// Test data.
		$product      = WC_Helper_Product::create_simple_product( array( 'stock_status' => 'outofstock' ) );
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id(), array( 'subscribe_date' => time() - 5 ) );

		// Make the product in stock.
		$product->set_stock_status( 'instock' );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check if the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );

		// Process the queue.
		$this->sync_process_notifications_batch();
		// Needs one more sanity-check to clean up the queue.
		$this->sync_process_notifications_batch();
		// Queue should be empty.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );

		// Check if the notification is delivered.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertFalse( $notification->is_queued() );
		$this->assertTrue( $notification->is_delivered() );
		// Ensure the notification is inactive.
		$this->assertFalse( $notification->is_active() );
	}

	/**
	 * Test the sync function for product in stock transition.
	 */
	public function test_product_on_backorder_transition() {

		// Test data.
		$product      = WC_Helper_Product::create_simple_product( array( 'stock_status' => 'outofstock' ) );
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id(), array( 'subscribe_date' => time() - 5 ) );

		// Make the product in stock.
		$product->set_stock_status( 'onbackorder' );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check if the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );

		// Process the queue.
		$this->sync_process_notifications_batch();
		// Needs one more sanity-check to clean up the queue.
		$this->sync_process_notifications_batch();
		// Queue should be empty.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );

		// Check if the notification is delivered.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertFalse( $notification->is_queued() );
		$this->assertTrue( $notification->is_delivered() );
		// Ensure the notification is inactive.
		$this->assertFalse( $notification->is_active() );
	}

	/**
	 * Test product reducing stock status.
	 */
	public function test_product_stock_status_reduced() {
		$product = new WC_Product();

		// Product should not have quantity and stock status should not be updated automatically if not managing stock.
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 5 );
		$product->save();
		$this->assertTrue( $product->is_in_stock() );

		// Sync.
		WC_BIS()->sync->sync();

		// Test notification (When subscribing to a instock product, the subscribe date is set to 0).
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id() );
		$this->assertEquals( 0, $notification->get_subscribe_date() );

		// Reduce stock but not to zero.
		$product->set_stock_quantity( 2 );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check if the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEquals( 0, $notification->get_subscribe_date() );
		$this->assertFalse( $notification->is_queued() );

		// Reduce to zero.
		$product->set_stock_quantity( 0 );
		$product->save();
		$this->assertFalse( $product->is_in_stock() );

		// Sync.
		WC_BIS()->sync->sync();

		// Check the subscribe date.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );
		$this->assertFalse( $notification->is_queued() );
	}

	/**
	 * Test product increasing stock status.
	 */
	public function test_product_stock_status_increased() {
		$product = new WC_Product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		// Test notification (When subscribing to a outofstock product, the subscribe date is set to now).
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id() );
		$this->assertEquals( 0, $notification->get_subscribe_date() );

		// Sync.
		WC_BIS()->sync->sync();

		// Check the subscribe date is set.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );

		// Increase stock.
		$product->set_stock_quantity( 1 );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );
	}

	/**
	 * Test product stock increasing and min stock threshold.
	 */
	public function test_product_stock_increased_min_threshold() {
		$product = new WC_Product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		// Test notification.
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product->get_id() );

		// Sync.
		WC_BIS()->sync->sync();

		// Check the subscribe date is set.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );

		// Set min stock threshold.
		update_option( 'wc_bis_stock_threshold', 5 );

		// Increase stock.
		$product->set_stock_quantity( 1 );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is not queued since the stock is below the threshold.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertFalse( $notification->is_queued() );

		// Increase stock.
		$product->set_stock_quantity( 5 );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is queued since the stock is above the threshold.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );
	}

	/**
	 * Test a notification to a variation product that uses the parent stock status.
	 */
	public function test_variation_product_parent_stock_status() {
		$variable = WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();

		// Set them outofstock and the manage stock to parent.
		$first_child = wc_get_product( $children[0] );
		$first_child->set_manage_stock( 'parent' );
		$first_child->set_stock_status( 'outofstock' );
		$first_child->save();
		$variable = wc_get_product( $variable->get_id() );
		$variable->set_stock_status( 'outofstock' );
		$variable->save();

		// Test notification.
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $first_child->get_id() );

		// Sync.
		WC_BIS()->sync->sync();

		// Check the subscribe date is set.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );

		// Set the parent to instock.
		$variable->set_stock_status( 'instock' );
		$variable->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );
	}

	/**
	 * Test a notification to a variation product that doesnt use the parent stock status.
	 */
	public function test_variation_product_individual_stock_status() {

		$product = WC_Helper_Product::create_variation_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$children = $product->get_children();

		// Sync (empty the queue).
		WC_BIS()->sync->sync();

		// Set them outofstock.
		$first_child = wc_get_product( $children[0] );
		$first_child->set_stock_status( 'outofstock' );
		$first_child->save();

		// Test notification.
		$user         = WC_Helper_Customer::create_customer();
		$notification = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $first_child->get_id() );

		// Sync.
		WC_BIS()->sync->sync();

		// Check the subscribe date is set.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_subscribe_date(), 10 );

		// Set the parent to instock.
		$product->set_stock_status( 'instock' );
		$product->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is not queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertFalse( $notification->is_queued() );

		// Set the child to instock.
		$first_child->set_stock_status( 'instock' );
		$first_child->save();

		// Sync.
		WC_BIS()->sync->sync();

		// Check the notification is queued.
		$notification = wc_bis_get_notification( $notification->get_id() );
		$this->assertTrue( $notification->is_queued() );
	}
}
