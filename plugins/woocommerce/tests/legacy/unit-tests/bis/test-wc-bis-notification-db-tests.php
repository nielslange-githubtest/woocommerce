<?php
/**
 * WC_BIS_Notification_DB_Tests test cases.
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    3.0.0
 */

/**
 * Test cases for the 'WC_BIS_Notification_DB_Tests' class.
 *
 * @version 3.0.0
 */
class WC_BIS_Notification_DB_Tests extends WC_BIS_Test_Case {

	/**
	 * Tests the add() method.
	 *
	 * @covers WC_BIS_Notifications_DB::add
	 */
	public function test_add_notification() {
		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		try {
			// Expect an Exception when no product.
			$notification = WC_BIS()->db->notifications->add(
				array(
					'product_id' => 0,
					'user_email' => 'test@mail.com',
				)
			);
			$this->fail( 'Expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Product is empty.', $e->getMessage() );
		}

		// Expect an Exception when no user.
		try {
			$notification = WC_BIS()->db->notifications->add(
				array(
					'product_id' => $product->get_id(),
					'user_email' => '',
				)
			);
			$this->fail( 'Expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Customer is empty.', $e->getMessage() );
		}

		// Expect an Exception when invalid email.
		try {
			$notification = WC_BIS()->db->notifications->add(
				array(
					'product_id' => $product->get_id(),
					'user_email' => 'invalid',
				)
			);
			$this->fail( 'Expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Invalid e-mail: invalid.', $e->getMessage() );
		}

		// Create one.
		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$this->assertGreaterThan( 0, $notification_id );

		// Check object.
		$notification = WC_BIS()->db->notifications->get( $notification_id );
		$this->assertInstanceOf( 'WC_BIS_Notification_Data', $notification, 'Return object is WC_BIS_Notification_Data' );
		$this->assertEquals( $product->get_id(), $notification->get_product_id(), 'Product ID is set' );
		$this->assertEqualsWithDelta( time(), $notification->get_create_date(), 5 );
		$this->assertEquals( 0, $notification->get_subscribe_date(), 'Product is in stock' );
		$this->assertEquals( 0, $notification->get_last_notified_date(), 'Notification is not delivered ever' );
		$this->assertTrue( $notification->is_active(), 'Notification is active' );
		$this->assertFalse( $notification->is_queued(), 'Notification is not queued' );
		$this->assertFalse( $notification->is_delivered(), 'Notification is not sent' );

		// Finally check that you can add a notification with the same product and user.
		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$this->assertGreaterThan( 0, $notification_id );
		$this->assertEquals( 2, wc_bis_get_notifications_count( $product->get_id() ), 'Notification count is 2' );
	}

	/**
	 * Tests the update method.
	 *
	 * @covers WC_BIS_Notifications_DB::update
	 */
	public function test_update_notification() {

		$updated = WC_BIS()->db->notifications->update(
			0,
			array(
				'is_active' => 'off',
			)
		);
		$this->assertFalse( $updated, 'Returns false for non-existant notification' );

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$notification_id           = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$notification              = WC_BIS()->db->notifications->get( $notification_id );
		$notification_created_time = $notification->get_create_date();
		$this->assertEqualsWithDelta( time(), $notification_created_time, 1 );

		// Set an invalid email.
		try {
			$updated = WC_BIS()->db->notifications->update(
				$notification_id,
				array(
					'user_email' => 'invalid',
				)
			);
			$this->fail( 'Expected Exception not thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Invalid e-mail: invalid.', $e->getMessage() );
		}

		// Update.
		$updated = WC_BIS()->db->notifications->update(
			$notification_id,
			array(
				'is_active' => 'off',
			)
		);
		$this->assertEquals( $notification_id, $updated, 'Returns notification ID' );
		$notification = WC_BIS()->db->notifications->get( $notification_id );
		$this->assertFalse( $notification->is_active(), 'Notification is inactive' );
		$this->assertEquals( $notification_created_time, $notification->get_create_date() );
	}

	/**
	 * Tests the delete method.
	 *
	 * @covers WC_BIS_Notifications_DB::delete
	 */
	public function test_delete_notification() {

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$this->assertGreaterThan( 0, $notification_id );

		WC_BIS()->db->notifications->delete( $notification_id );
		$this->assertFalse( WC_BIS()->db->notifications->get( $notification_id ), 'Notification is deleted' );
		$this->assertEquals( 0, wc_bis_get_notifications_count( $product->get_id() ), 'Notification count is 0' );
	}

	/**
	 * Test the get method.
	 *
	 * @covers WC_BIS_Notifications_DB::get
	 */
	public function test_get_notification() {

		$notification = WC_BIS()->db->notifications->get( 0 );
		$this->assertFalse( $notification, 'Returns false for non-existant notification' );

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$notification    = WC_BIS()->db->notifications->get( $notification_id );
		$this->assertInstanceOf( 'WC_BIS_Notification_Data', $notification, 'Return object is WC_BIS_Notification_Data' );
		$this->assertEquals( $product->get_id(), $notification->get_product_id(), 'Product ID is set' );

		// Test get by email.
		$notification = WC_BIS()->db->notifications->get( $notification );
		$this->assertInstanceOf( 'WC_BIS_Notification_Data', $notification, 'Return object is WC_BIS_Notification_Data' );

		// Test get by array.
		$notification = WC_BIS()->db->notifications->get( (object) array( 'id' => $notification_id ) );
		$this->assertInstanceOf( 'WC_BIS_Notification_Data', $notification, 'Return object is WC_BIS_Notification_Data' );
	}

	/**
	 * Test basic query functionality.
	 *
	 * @covers WC_BIS_Notifications_DB::query
	 */
	public function test_query_notifications() {

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		// Create three notifications.
		$notification_id   = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$notification_id_2 = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test2@mail.com',
			)
		);
		$notification_id_3 = WC_BIS()->db->notifications->add(
			array(
				'product_id'  => $product->get_id(),
				'user_email'  => 'test3@mail.com',
				'is_active'   => 'off',
				'is_verified' => 'no',
			)
		);

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
			)
		);
		// Check that all notifications are returned.
		$this->assertCount( 3, $notifications, 'Returns all notifications' );

		// Query notifications by product ID as an array.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => array( $product->get_id() ),
			)
		);
		$this->assertCount( 3, $notifications, 'Returns all notifications' );

		// Query notifications by type.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'type'       => 'one-time',
			)
		);
		$this->assertCount( 3, $notifications, 'Returns all notifications' );

		// Query notifications by user email.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one notification' );

		// Query notifications by an array of user emails.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'user_email' => array( 'test@mail.com', 'test2@mail.com' ),
			)
		);
		$this->assertCount( 2, $notifications, 'Returns two notifications' );

		// Add another notification and test search functionality.
		$notification_id_4 = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test33@mail.com',
				'is_queued'  => 'on',
			)
		);
		$notifications     = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'search'     => 'test3',
			)
		);
		$this->assertCount( 2, $notifications, 'Returns two notifications' );

		// Query active notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'is_active'  => 'on',
			)
		);
		$this->assertCount( 3, $notifications, 'Returns three active notifications' );

		// Query verified notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'  => $product->get_id(),
				'is_verified' => 'yes',
			)
		);
		$this->assertCount( 3, $notifications, 'Returns three verified notifications' );

		// Query queued notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'is_queued'  => 'on',
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one queued notification' );

		// Query all notifications without any filters.
		$notifications = WC_BIS()->db->notifications->query( array() );
		$this->assertCount( 4, $notifications, 'Returns all notifications' );

		// Test default return values (arrays).
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
			)
		);
		$this->assertIsArray( $notifications[0], 'Return objects are arrays' );

		// Test return values as IDs.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'return'     => 'ids',
			)
		);
		$this->assertContainsOnly( 'int', $notifications, 'Return values are integers' );
		$this->assertSame( array( $notification_id, $notification_id_2, $notification_id_3, $notification_id_4 ), $notifications, 'Return values are correct' );

		// Test ordering of return values.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'return'     => 'ids',
				'order_by'   => array( 'id' => 'DESC' ),
			)
		);
		$this->assertSame( array( $notification_id_4, $notification_id_3, $notification_id_2, $notification_id ), $notifications, 'Return values are ordered' );

		// Test return values as objects.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'return'     => 'objects',
			)
		);
		$this->assertContainsOnlyInstancesOf( 'WC_BIS_Notification_Data', $notifications, 'Return objects are WC_BIS_Notification_Data' );

		// Test count of notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'count'      => true,
				'return'     => 'ids', // This is here to ensure that count overrides return arg.
			)
		);
		$this->assertSame( 4, $notifications, 'Return count is correct' );

		// Test limit of notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'limit'      => 2,
				'return'     => 'ids',
			)
		);
		$this->assertSame( array( $notification_id, $notification_id_2 ), $notifications, 'Return values are limited' );

		// Test offset of notifications.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'limit'      => 4, // Add a bit more than the count.
				'offset'     => 1,
				'return'     => 'ids',
			)
		);
		$this->assertSame( array( $notification_id_2, $notification_id_3, $notification_id_4 ), $notifications, 'Return values are offset' );
	}

	/**
	 * Test date-based query functionality.
	 *
	 * @covers WC_BIS_Notifications_DB::query
	 */
	public function test_query_notifications_with_date_ranges() {

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$month_before_timestamp        = strtotime( '-1 month' );
		$two_months_before_timestamp   = strtotime( '-2 months' );
		$three_months_before_timestamp = strtotime( '-3 months' );

		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id'  => $product->get_id(),
				'user_email'  => 'test@mail.com',
				'create_date' => $month_before_timestamp,
			)
		);
		$notification    = WC_BIS()->db->notifications->get( $notification_id );
		$this->assertEquals( $month_before_timestamp, $notification->get_create_date(), 'Notification is created a month ago' );

		// Fetch outside scope.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
				'start_date' => $three_months_before_timestamp,
				'end_date'   => $two_months_before_timestamp,
			)
		);
		$this->assertCount( 0, $notifications, 'Returns no notifications' );

		// Fetch on the end-edge scope.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
				'start_date' => $two_months_before_timestamp,
				'end_date'   => $month_before_timestamp,
			)
		);
		$this->assertCount( 0, $notifications, 'Returns no notifications' );

		// Fetch on the start-edge scope.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
				'start_date' => $month_before_timestamp,
				'end_date'   => time(),
			)
		);

		$this->assertCount( 1, $notifications, 'Returns one notification' );

		// Fetch the "create_date" argument on the start-edge.
		// Note: Maybe consider removing that.
		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'  => $product->get_id(),
				'user_email'  => 'test@mail.com',
				'create_date' => $month_before_timestamp,
			)
		);

		$this->assertCount( 1, $notifications, 'Returns one notification' );
	}

	/**
	 * Test product-status-based query functionality.
	 *
	 * @covers WC_BIS_Notifications_DB::query
	 */
	public function test_query_notifications_with_product_status() {

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_status' => 'publish',
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one notifications' );

		$product->set_status( 'private' );
		$product->save();

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_status' => 'publish',
			)
		);
		$this->assertCount( 0, $notifications, 'Returns no notifications' );
	}

	/**
	 * Test product-exists-based query functionality.
	 *
	 * @covers WC_BIS_Notifications_DB::query
	 */
	public function test_query_notifications_with_product_exists() {

		$product = WC_Helper_Product::create_simple_product(
			array(
				'stock_status' => 'instock',
				'manage_stock' => false,
			)
		);

		$notification_id = WC_BIS()->db->notifications->add(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'test@mail.com',
			)
		);

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_exists' => true, // True means if does exists.
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one notification' );

		$product->delete();

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_exists' => true, // True means if does exists.
			)
		);
		$this->assertCount( 0, $notifications, 'Returns no notifications' );

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_exists' => false, // True means if does exists.
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one notification' );

		// Now force-delete the product.
		$product->delete( true );

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_exists' => true, // True means if does exists.
			)
		);
		$this->assertCount( 0, $notifications, 'Returns no notifications' );

		$notifications = WC_BIS()->db->notifications->query(
			array(
				'product_id'     => $product->get_id(),
				'product_exists' => false, // True means if does exists.
			)
		);
		$this->assertCount( 1, $notifications, 'Returns one notification' );
	}
}
