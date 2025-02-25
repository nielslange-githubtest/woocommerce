<?php
/**
 * WC_BIS_Notifications_Data_Tests test cases
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    3.0.0
 */

/**
 * Test cases for the 'WC_BIS_Notifications_Data_Tests' class.
 *
 * @since 3.0.0
 */
class WC_BIS_Notifications_Data_Tests extends WC_BIS_Test_Case {

	/**
	 * Test notification data default values.
	 */
	public function test_notification_data_default_values() {
		$notification = new WC_BIS_Notification_Data();
		$this->assertEquals( $notification->get_id(), 0 );
		$this->assertEquals( $notification->get_product_id(), 0 );
		$this->assertEquals( $notification->get_type(), 'one-time' );
		$this->assertEquals( $notification->get_user_id(), 0 );
		$this->assertEquals( $notification->get_user_email(), '' );
		$this->assertEquals( $notification->get_create_date(), 0 );
		$this->assertEquals( $notification->get_last_notified_date(), 0 );
		$this->assertTrue( $notification->is_active() );
		$this->assertFalse( $notification->is_delivered() );
		$this->assertFalse( $notification->is_queued() );
	}

	/**
	 * Test notification data set values.
	 */
	public function test_notification_data_set_values() {
		$notification = new WC_BIS_Notification_Data();
		$notification->set_product_id( 1 );
		$notification->set_type( 'one-time' );
		$notification->set_user_id( 2 );
		$notification->set_user_email( 'test@example.test' );
		$notification->set_create_date( 1234567890 );
		$notification->set_last_notified_date( 1234567890 );
		$notification->set_queued_status( 'on' );
		$notification->set_active( 'off' );

		$this->assertEquals( $notification->get_product_id(), 1 );
		$this->assertEquals( $notification->get_type(), 'one-time' );
		$this->assertEquals( $notification->get_user_id(), 2 );
		$this->assertEquals( $notification->get_user_email(), 'test@example.test' );
		$this->assertEquals( $notification->get_create_date(), 1234567890 );
		$this->assertEquals( $notification->get_last_notified_date(), 1234567890 );
		$this->assertFalse( $notification->is_active() );
		$this->assertFalse( $notification->is_delivered() );

		// Save.
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( $notification->get_product_id(), 1 );
		$this->assertEquals( $notification->get_type(), 'one-time' );
		$this->assertEquals( $notification->get_user_id(), 2 );
		$this->assertEquals( $notification->get_user_email(), 'test@example.test' );
		$this->assertEquals( $notification->get_create_date(), 1234567890 );
		$this->assertEquals( $notification->get_last_notified_date(), 1234567890 );
		$this->assertFalse( $notification->is_active() );
		$this->assertFalse( $notification->is_delivered() );
		$this->assertTrue( $notification->is_queued() );
	}

	/**
	 * Test notification data set values.
	 */
	public function test_notification_data_active_status() {
		$notification = new WC_BIS_Notification_Data();
		$notification->set_active( 'on' );
		$this->assertTrue( $notification->is_active() );
		$notification->set_active( 'off' );
		$this->assertFalse( $notification->is_active() );
		$notification->set_active( 'on' );
		$notification->set_active( 'random' );
		$this->assertFalse( $notification->is_active() );
	}

	/**
	 * Test notification data set values.
	 */
	public function test_notifications_data_queued_status() {
		$notification = new WC_BIS_Notification_Data();
		$notification->set_queued_status( 'on' );
		$this->assertTrue( $notification->is_queued() );
		$notification->set_queued_status( 'off' );
		$this->assertFalse( $notification->is_queued() );
		$notification->set_queued_status( 'on' );
		$notification->set_queued_status( 'random' );
		$this->assertFalse( $notification->is_queued() );
	}

	/**
	 * Test notification data set values.
	 */
	public function test_notifications_data_delivered_status() {
		$notification = new WC_BIS_Notification_Data();
		// Default value is false.
		$this->assertFalse( $notification->is_delivered() );

		// Only subscribed notifications can be delivered.
		$base_time = time() - HOUR_IN_SECONDS;
		$notification->set_last_notified_date( $base_time );
		$this->assertFalse( $notification->is_delivered() );

		// Same time should not be delivered.
		$notification->set_subscribe_date( $base_time );
		$this->assertFalse( $notification->is_delivered() );

		// Add a previous time for the subscribe date.
		$notification->set_subscribe_date( $base_time - HOUR_IN_SECONDS );
		$this->assertTrue( $notification->is_delivered() );

		// Set to now.
		$notification->set_subscribe_date( time() );
		$this->assertFalse( $notification->is_delivered() );
	}

	/**
	 * Test notification data constructor.
	 */
	public function test_notification_data_constructor() {
		$notification = new WC_BIS_Notification_Data();
		$notification->set_product_id( 1 );
		$notification->save();

		// Test constructor with ID.
		$notification2 = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( $notification->get_id(), $notification2->get_id() );
		$this->assertEquals( $notification->get_product_id(), $notification2->get_product_id() );

		// Test constructor with object.
		$notification3 = new WC_BIS_Notification_Data( $notification );
		$this->assertEquals( $notification->get_id(), $notification3->get_id() );
		$this->assertEquals( $notification->get_product_id(), $notification3->get_product_id() );

		// Test constructor with invalid ID.
		$notification4 = new WC_BIS_Notification_Data( 999999 );
		$this->assertEquals( 0, $notification4->get_id() );
	}

	/**
	 * Test notification data validate before save.
	 */
	public function test_notification_data_validate_before_save() {

		// Setting activating a non-verified notification.
		$notification = new WC_BIS_Notification_Data();
		$notification->set_verified_status( 'off' );
		$notification->set_active( 'on' );
		$notification->save();
		$this->assertFalse( $notification->is_active() );

		// Invalid email.
		$notification = new WC_BIS_Notification_Data();
		$notification->set_user_email( 'invalid-email' );
		$notification->save();
		$this->assertEquals( '', $notification->get_user_email() );
	}

	/**
	 * Test notification data metadata.
	 */
	public function test_notification_data_metadata() {
		$notification = new WC_BIS_Notification_Data();
		$this->assertEquals( '', $notification->get_meta( 'test' ) );
		$notification->add_meta( 'test', 'value' );
		$this->assertEquals( 'value', $notification->get_meta( 'test' ) );
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( 'value', $notification->get_meta( 'test' ) );
		$notification->delete_meta( 'test' );
		$this->assertEquals( '', $notification->get_meta( 'test' ) );

		// Test saving.
		$notification->add_meta( 'test', 'value' );
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( 'value', $notification->get_meta( 'test' ) );

		// Test saving with multiple values.
		$notification->add_meta( 'test2', 'value2' );
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( 'value', $notification->get_meta( 'test' ) );
		$this->assertEquals( 'value2', $notification->get_meta( 'test2' ) );
		$this->assertCount( 5, $notification->get_meta_data() ); // +3 default values (_hash_key, _hash_iv, _customer_location_data).

		// Test updating.
		$notification->update_meta( 'test', 'value3' );
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( 'value3', $notification->get_meta( 'test' ) );
		$this->assertEquals( 'value2', $notification->get_meta( 'test2' ) );

		// Test deleting.
		$notification->delete_meta( 'test' );
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( '', $notification->get_meta( 'test' ) );
		$this->assertEquals( 'value2', $notification->get_meta( 'test2' ) );

		// Test a serialized string.
		$notification->add_meta( 'test', array( 'value' ) );
		$notification->add_meta( 'test2', serialize( array( 'value2' ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$notification->save();
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertEquals( array( 'value' ), $notification->get_meta( 'test' ) );
		$this->assertEquals( array( 'value2' ), $notification->get_meta( 'test2' ) );
	}

	/**
	 * Test notification data metadata defaults.
	 */
	public function test_notification_data_metadata_defaults() {
		$notification = new WC_BIS_Notification_Data();
		$notification->save();
		$this->assertNotEmpty( $notification->get_meta( '_hash_key' ) );
		$this->assertNotEmpty( $notification->get_meta( '_hash_iv' ) );
		$this->assertNotEmpty( $notification->get_meta( '_customer_location_data' ) );

		$hash = $notification->get_hash();
		$this->assertNotEmpty( $hash );

		// Refecth.
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertTrue( $notification->validate_hash( $hash ) );
	}

	/**
	 * Test notification data verification process.
	 */
	public function test_notification_data_verification() {
		$notification = new WC_BIS_Notification_Data();
		$notification->set_verified_status( 'off' );
		$notification->set_active( 'on' );
		$notification->save();
		$this->assertFalse( $notification->is_active() );
		$this->assertTrue( $notification->maybe_setup_verification_data() );
		$this->assertNotEmpty( $notification->get_meta( '_verification_code' ) );
		$this->assertNotEmpty( $notification->get_meta( '_verification_created_at' ) );
		$this->assertNotEmpty( $notification->get_meta( '_verification_key' ) );
		$this->assertNotEmpty( $notification->get_meta( '_verification_iv' ) );
		$notification->save();
		$code = $notification->get_meta( '_verification_code' );
		$hash = $notification->get_verification_hash( $code );

		// Refetch.
		$notification = new WC_BIS_Notification_Data( $notification->get_id() );
		$this->assertTrue( $notification->validate_verification_code( $code, $hash ) );
	}
}
