<?php
/**
 * WC_BIS_Sync_Tasks_Tests test cases
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    3.0.0
 */

/**
 * Test cases for the 'WC_BIS_Sync_Tasks_Tests' class.
 *
 * @since 3.0.0
 */
class WC_BIS_Sync_Tasks_Tests extends WC_BIS_Test_Case {

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
	 * Test when a product goes out of stock and there are notifications not being subscribed.
	 */
	public function test_non_subscribed_notifications_when_product_goes_in_stock() {
		// Create test in-stock Products and a User.
		$product1 = WC_Helper_Product::create_simple_product();
		$user     = WC_Helper_Customer::create_customer();

		// Create test Notifications.
		$subscription = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product1->get_id() );
		$this->assertEquals( 1, wc_bis_get_notifications( array( 'count' => true ) ) );
		$this->assertEquals( 0, $subscription->get_subscribe_date() );

		// Simulate in stock transition.
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product1->get_id(),
			)
		);

		// Refetch.
		$subscription = wc_bis_get_notification( $subscription->get_id() );
		$this->assertFalse( $subscription->is_queued() );
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );
	}

	/**
	 * Test when a product goes out of stock and there are notifications not being subscribed.
	 */
	public function test_non_subscribed_notifications_when_product_goes_out_of_stock() {
		// Create test in-stock Products and a User.
		$product1 = WC_Helper_Product::create_simple_product();
		$user     = WC_Helper_Customer::create_customer();

		// Create test Notifications.
		$subscription = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product1->get_id() );
		$this->assertEquals( 1, wc_bis_get_notifications( array( 'count' => true ) ) );
		$this->assertEquals( 0, $subscription->get_subscribe_date() );

		// Simulate out of stock transition.
		WC_BIS_Sync_Tasks::handle_outofstock_products(
			array(
				$product1->get_id(),
			)
		);

		// Refetch.
		$subscription = wc_bis_get_notification( $subscription->get_id() );
		$this->assertEqualsWithDelta( time(), $subscription->get_subscribe_date(), 3 );
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );
	}

	/**
	 * Test notifications are queued when a product goes in stock.
	 */
	public function test_subscribed_notifications_when_product_goes_in_stock() {

		// Create test out-of-stock Products and a User.
		$product1 = WC_Helper_Product::create_simple_product();
		$user     = WC_Helper_Customer::create_customer();

		// Create test Notifications.
		$subscription = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product1->get_id(), array( 'subscribe_date' => time() - 5 ) );
		$this->assertEquals( 1, wc_bis_get_notifications( array( 'count' => true ) ) );
		$this->assertEqualsWithDelta( time(), $subscription->get_subscribe_date(), 5 );

		// Simulate in stock transition.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product1->get_id(),
			)
		);

		// Refetch.
		$subscription = wc_bis_get_notification( $subscription->get_id() );
		// Subscription should be queued.
		$this->assertTrue( $subscription->is_queued() );
		$this->assertGreaterThan( time(), as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );
	}

	/**
	 * Test that only subscribed notifications are queued when a product goes in stock.
	 */
	public function test_broadcasting_only_subscribed_notifications() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();
		$user     = WC_Helper_Customer::create_customer();

		$subscription1 = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product1->get_id() );
		$subscription2 = WC_BIS_Product_Helpers::create_customer_notification( $user->get_id(), $product2->get_id() );

		$this->assertEquals( 2, wc_bis_get_notifications( array( 'count' => true ) ) );

		// Simulate out of stock transition for the first product.
		WC_BIS_Sync_Tasks::handle_outofstock_products(
			array(
				$product1->get_id(),
			)
		);

		// Refetch and check waiting for time.
		$subscription1 = wc_bis_get_notification( $subscription1->get_id() );
		$subscription2 = wc_bis_get_notification( $subscription2->get_id() );
		$this->assertEqualsWithDelta( time(), $subscription1->get_subscribe_date(), 5 );
		$this->assertEquals( 0, $subscription2->get_subscribe_date() );

		// Simulate in stock transition for both products.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product1->get_id(),
				$product2->get_id(),
			)
		);

		// Refetch and check queue status. Only one should be queued.
		$subscription1 = wc_bis_get_notification( $subscription1->get_id() );
		$subscription2 = wc_bis_get_notification( $subscription2->get_id() );
		$this->assertTrue( $subscription1->is_queued() );
		$this->assertFalse( $subscription2->is_queued() );

		// Process the queue, but wait a second to clear the `is_delivered` flag.
		sleep( 1 );
		$this->sync_process_notifications_batch();
		// Needs one more sanity-check to clean up the queue.
		$this->sync_process_notifications_batch();
		// Queue should be empty.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );

		// Refetch and check queue status. First should be processed, second should be idle.
		$subscription1 = wc_bis_get_notification( $subscription1->get_id() );
		$subscription2 = wc_bis_get_notification( $subscription2->get_id() );
		$this->assertFalse( $subscription1->is_queued() );
		$this->assertTrue( $subscription1->is_delivered() );
		$this->assertFalse( $subscription2->is_queued() );
		$this->assertFalse( $subscription2->is_delivered() );
	}

	/**
	 * Test that notifications for the same product are not queued multiple times within a short period.
	 * See filter `woocommerce_bis_last_sent_throttle`.
	 */
	public function test_avoiding_spam() {
		$product       = WC_Helper_Product::create_simple_product();
		$subscriptions = WC_BIS_Product_Helpers::create_product_notifications( $product->get_id(), 2, array( 'subscribe_date' => time() - 100 ) );
		$this->assertEquals( 2, wc_bis_get_notifications( array( 'count' => true ) ) );

		// Simulate in stock transition.
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product->get_id(),
			)
		);

		// Refetch and check queue status. Both should be queued.
		$subscription1 = wc_bis_get_notification( $subscriptions[0]->get_id() );
		$subscription2 = wc_bis_get_notification( $subscriptions[1]->get_id() );
		$this->assertTrue( $subscription1->is_queued() );
		$this->assertTrue( $subscription2->is_queued() );

		// Process the queue.
		$this->sync_process_notifications_batch();
		// Needs one more sanity-check to clean up the queue.
		$this->sync_process_notifications_batch();
		// Queue should be empty.
		$this->assertEquals( 0, as_next_scheduled_action( 'wc_bis_process_notifications_batch' ) );

		// Refetch and check queue status. Both should be processed.
		$subscription1 = wc_bis_get_notification( $subscriptions[0]->get_id() );
		$subscription2 = wc_bis_get_notification( $subscriptions[1]->get_id() );
		$this->assertFalse( $subscription1->is_queued() );
		$this->assertTrue( $subscription1->is_delivered() );
		$this->assertFalse( $subscription2->is_queued() );
		$this->assertTrue( $subscription2->is_delivered() );

		// Re-activate the subscriptions.
		$subscription1->set_active( 'on' );
		$subscription1->save();
		$subscription2->set_active( 'on' );
		$subscription2->save();

		// Wait for a sec, and then simulate an out-of-stock transition.
		sleep( 1 );
		WC_BIS_Sync_Tasks::handle_outofstock_products(
			array(
				$product->get_id(),
			)
		);

		// Refetch and check waiting for time.
		$subscription1 = wc_bis_get_notification( $subscriptions[0]->get_id() );
		$subscription2 = wc_bis_get_notification( $subscriptions[1]->get_id() );
		$this->assertEqualsWithDelta( time(), $subscription1->get_subscribe_date(), 10 );
		$this->assertEqualsWithDelta( time(), $subscription2->get_subscribe_date(), 10 );

		// Simulate in stock transition again and check that items aren't in queue.
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product->get_id(),
			)
		);

		// Refetch and check queue status. Both should be idle. Spam protection kicks in.
		$subscription1 = wc_bis_get_notification( $subscriptions[0]->get_id() );
		$subscription2 = wc_bis_get_notification( $subscriptions[1]->get_id() );
		$this->assertFalse( $subscription1->is_queued() );
		$this->assertFalse( $subscription1->is_delivered() );
		$this->assertFalse( $subscription2->is_queued() );
		$this->assertFalse( $subscription2->is_delivered() );

		// Wait for a sec, and then simulate an out-of-stock transition.
		sleep( 1 );
		WC_BIS_Sync_Tasks::handle_outofstock_products(
			array(
				$product->get_id(),
			)
		);

		// Check that forcing the queue works.
		WC_BIS_Sync_Tasks::handle_instock_products(
			array(
				$product->get_id(),
			),
			true
		);

		// Refetch and check queue status. Both should be queued.
		$subscription1 = wc_bis_get_notification( $subscriptions[0]->get_id() );
		$subscription2 = wc_bis_get_notification( $subscriptions[1]->get_id() );
		$this->assertTrue( $subscription1->is_queued() );
		$this->assertTrue( $subscription2->is_queued() );
	}
}
