<?php
/**
 * Product helpers
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

/**
 * WC_BIS_Product_Helpers class.
 *
 * @version 3.0.0
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_BIS_Product_Helpers {

	/**
	 * Creates a customer notification.
	 *
	 * @param int   $user_id    The user ID.
	 * @param int   $product_id The product ID.
	 * @param array $args       The args.
	 * @return bool|WC_BIS_Notification_Data
	 */
	public static function create_customer_notification( int $user_id, int $product_id, array $args = array() ): WC_BIS_Notification_Data {

		// Default args.
		$args = wp_parse_args(
			$args,
			array(
				'user_id'        => $user_id,
				'user_email'     => get_userdata( $user_id )->user_email,
				'product_id'     => $product_id,
				'subscribe_date' => 0,
			)
		);

		$notification_id = WC_BIS()->db->notifications->add( $args );

		$notification = wc_bis_get_notification( $notification_id );
		return $notification;
	}

	/**
	 * Creates product notifications.
	 *
	 * @param int   $product_id              The product.
	 * @param int   $number_of_notifications The number of notifications.
	 * @param array $args                    The args.
	 * @return array
	 */
	public static function create_product_notifications( int $product_id, int $number_of_notifications = 1, array $args = array() ): array {
		$notifications = array();

		for ( $i = 0; $i < $number_of_notifications; $i++ ) {

			// Guests.
			$user_email = 'test' . wp_rand() . '@example.com';

			$args = wp_parse_args(
				$args,
				array(
					'user_id'        => 0,
					'user_email'     => $user_email,
					'product_id'     => $product_id,
					'subscribe_date' => 0,
				)
			);

			$notification_id = WC_BIS()->db->notifications->add( $args );

			$notification    = wc_bis_get_notification( $notification_id );
			$notifications[] = $notification;
		}

		return $notifications;
	}
}
