<?php
/**
 * WC_BIS_Sync_Tasks class
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
 * Sync stock Queue Controller Class.
 *
 * @class    WC_BIS_Sync_Tasks
 * @version  9.9.0
 */
class WC_BIS_Sync_Tasks {

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public static function init() {

		// Main triggers for stock tasks.
		add_action( 'woocommerce_bis_sync_handle_outofstock_products', array( __CLASS__, 'handle_outofstock_products' ) );
		add_action( 'woocommerce_bis_sync_handle_instock_products', array( __CLASS__, 'handle_instock_products' ) );

		// Async tasks.
		add_action( 'wc_bis_daily', array( __CLASS__, 'do_wc_bis_daily' ) );
		add_action( 'wc_bis_process_notifications_batch', array( __CLASS__, 'async_process_notifications_batch' ), 10, 2 );

		// Sync product delete.
		add_action( 'before_delete_post', array( __CLASS__, 'handle_product_delete' ), 0 );

		// Force queue notifications.
		add_action( 'admin_init', array( __CLASS__, 'force_queue_notifications' ) );

		// Add hook to auto-remove notices after display.
		add_action( 'admin_notices', array( __CLASS__, 'auto_remove_notices' ), 99 );
	}

	/*****************************************
	 * Instock tasks.
	 ****************************************/

	/**
	 * Prepare notifications, mark queue status.
	 *
	 * @param  array $product_ids Array of product IDs to process.
	 * @param  bool  $force       Whether to force the queue regardless of throttle.
	 * @return void
	 */
	public static function handle_instock_products( $product_ids, $force = false ) {
		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$last_sent_throttle            = wc_bis_get_minimum_time_between_notifications();
		$throttle_timestamp            = $force || 0 === $last_sent_throttle ? 0 : time() - $last_sent_throttle;
		$count_results                 = WC_BIS()->db->notifications->get_subscribed_notifications_count_per_product( $product_ids, $throttle_timestamp );
		$notifications_count           = $count_results['allowed'];
		$throttled_notifications_count = $count_results['throttled'];

		// Check for recently sent notifications.
		if ( $throttled_notifications_count > 0 && class_exists( 'WC_Admin_Notices' ) ) {
			self::handle_spam_notices( $product_ids, $throttled_notifications_count );
		}

		if ( 0 === $notifications_count ) {
			return;
		}

		if ( wc_bis_debug_enabled() ) {
			wc_get_logger()->info(
				sprintf( 'Sending %d notifications for products that are back in stock.', $notifications_count ),
				array(
					'source'      => 'wc_bis_sync_logs',
					'product_ids' => $product_ids,
				)
			);
		}

		// Set queued status in bulk.
		WC_BIS()->db->notifications->bulk_enqueue_by_product_id( $product_ids, $force );

		// Initiate the BG queue.
		self::start_notifications_queue( $product_ids, $notifications_count );
	}

	/**
	 * Handle spam notices.
	 *
	 * @since 9.9.0
	 * @param  array $product_ids Array of product IDs.
	 * @param  int   $spam_count  Number of spam notifications.
	 * @return void
	 */
	private static function handle_spam_notices( array $product_ids, int $spam_count ) {
		$notice_text = sprintf(
			/* translators: %d: number of notifications */
			_n(
				'%d identical back-in-stock notification was sent to the same customer recently. This time, we skipped it to prevent spamming.',
				'%d identical back-in-stock notifications were sent to the same customers recently. This time, we skipped them to prevent spamming.',
				$spam_count,
				'woocommerce'
			),
			$spam_count
		);

		// Get current product id based on product or variation save.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_REQUEST['ID'] ) ) {
			$post_id = absint( $_REQUEST['ID'] );
		} elseif ( isset( $_REQUEST['product_id'] ) ) {
			$post_id = absint( $_REQUEST['product_id'] );
		} elseif ( isset( $_REQUEST['post_ID'] ) ) {
			$post_id = absint( $_REQUEST['post_ID'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		if ( isset( $post_id ) && 0 < $post_id ) {
			$notice_text .= sprintf(
				' <a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'wc_bis_force_queue' => implode( ',', $product_ids ),
							'_wpnonce'           => wp_create_nonce( 'wc_bis_force_queue' ),
						),
						admin_url( sprintf( 'post.php?post=%d&action=edit', $post_id ) )
					)
				),
				esc_html__( 'Send anyway', 'woocommerce' )
			);
		}

		$notice_id = 'bis_spam_notice_' . uniqid();
		WC_Admin_Notices::add_notice( $notice_id, true );
		update_option(
			'woocommerce_admin_notice_' . $notice_id,
			wp_kses_post( $notice_text )
		);
	}

	/**
	 * Bypass throttle and force queue notifications.
	 *
	 * @return void
	 */
	public static function force_queue_notifications() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! isset( $_GET['wc_bis_force_queue'] ) ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'wc_bis_force_queue' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die(
				esc_html__( 'Invalid nonce verification', 'woocommerce' ),
				esc_html__( 'Error', 'woocommerce' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		$product_ids = explode( ',', urldecode( wc_clean( wp_unslash( $_GET['wc_bis_force_queue'] ) ) ) );
		self::handle_instock_products( $product_ids, true );
		$url = remove_query_arg( 'wc_bis_force_queue' );
		wp_safe_redirect( $url );
		exit;
	}

	/*****************************************
	 * Notification queue.
	 ****************************************/

	/**
	 * Trigger the notification queue.
	 *
	 * @param  array $product_ids       Array of product IDs.
	 * @param  int   $last_known_count Number of notifications to process.
	 * @return void
	 */
	public static function start_notifications_queue( $product_ids, $last_known_count ) {

		// Give it some 'room' for last-minute calls.
		$batches = ceil( $last_known_count / self::get_batch_size() ) + 5;
		// Queue first batch.
		$args = array(
			'product_id' => $product_ids,
			'batch'      => 1,
			'batches'    => $batches,
		);

		if ( class_exists( 'WC_Admin_Notices' ) ) {
			$queue_url = admin_url( 'admin.php?page=bis_notifications&status=queued_bis_notifications' );

			$notice_text = sprintf(
				/* translators: %1$s: URL, %2$d: notifications count */
				_n(
					'%2$d back-in-stock notification is now <a href="%1$s">queued for delivery</a> in the next few minutes.',
					'%2$d back-in-stock notifications are now <a href="%1$s">queued for delivery</a> in the next few minutes.',
					$last_known_count,
					'woocommerce'
				),
				esc_url( $queue_url ),
				$last_known_count
			);

			$notice_id = 'bis_queue_notice_' . uniqid();
			WC_Admin_Notices::add_notice( $notice_id, true );
			update_option(
				'woocommerce_admin_notice_' . $notice_id,
				wp_kses_post( $notice_text )
			);
		}

		if ( ! WC()->queue()->get_next( 'wc_bis_process_notifications_batch', array( 'args' => $args ), 'wc_bis_notifications' ) ) {

			/**
			 * Filter: woocommerce_bis_first_batch_delay
			 *
			 * Schedule the first batch with a 60 sec delay.
			 *
			 * @since 9.9.0
			 *
			 * @param int   $throttle Throttle time in seconds between each batch.
			 * @param array $product_ids
			 */
			$first_throttle = (int) apply_filters( 'woocommerce_bis_first_batch_delay', MINUTE_IN_SECONDS, $product_ids );
			WC()->queue()->schedule_single( time() + $first_throttle, 'wc_bis_process_notifications_batch', array( 'args' => $args ), 'wc_bis_notifications' );
		}
	}

	/**
	 * Process single batch of notifications for given product.
	 *
	 * @param  array $args Array of arguments containing product_id, batch, and batches.
	 * @return void
	 */
	public static function async_process_notifications_batch( $args ) {

		// Fetch Notifications.
		$query_args    = array(
			'return'     => 'objects',
			'product_id' => $args['product_id'],
			'is_queued'  => 'on',
			'is_active'  => 'on',
			'limit'      => self::get_batch_size(),
		);
		$notifications = wc_bis_get_notifications( $query_args );
		if ( ! empty( $notifications ) ) {
			// Do the work.
			foreach ( $notifications as $notification ) {
				WC_Emails::instance();

				/**
				 * Filter: woocommerce_bis_pre_send_notification
				 *
				 * Prevent or manage sending a specific notification.
				 *
				 * @since 9.9.0
				 * @param bool $pre_send       Whether to prevent sending.
				 * @param int  $notification_id The notification ID.
				 * @return bool
				 */
				$pre_send = apply_filters( 'woocommerce_bis_pre_send_notification', null, $notification->get_id() );
				if ( ! is_null( $pre_send ) ) {
					continue;
				}

				/**
				 * Action: woocommerce_bis_send_notification_to_customer
				 *
				 * Fires to email notification to customer. See WC_BIS_Emails::setup_hooks().
				 *
				 * @since 1.0.0
				 *
				 * @param WC_BIS_Notification_Data $notification
				 * @return void
				 */
				do_action( 'woocommerce_bis_send_notification_to_customer', $notification );
			}

			if ( absint( $args['batch'] ) <= absint( $args['batches'] ) ) {

				// Queue next batch.
				$args['batch'] = absint( $args['batch'] ) + 1;
				WC()->queue()->add( 'wc_bis_process_notifications_batch', array( 'args' => $args ), 'wc_bis_notifications' );
			}
		}
	}

	/*****************************************
	 * Outofstock tasks.
	 ****************************************/

	/**
	 * Hande outofstock product notifications.
	 *
	 * @param  array $product_ids Array of product IDs.
	 * @return void
	 */
	public static function handle_outofstock_products( $product_ids ) {

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		WC_BIS()->db->notifications->bulk_renew_subscribe_dates_by_product_id( $product_ids );
	}

	/*****************************************
	 * Misc.
	 ****************************************/

	/**
	 * Number of notifications per batch.
	 *
	 * @return int
	 */
	protected static function get_batch_size() {
		return 50;
	}

	/**
	 * Deletes all notifications associated with the deleted product.
	 *
	 * @since 9.9.0
	 * @param  int    $post_id The post ID being deleted.
	 * @param  object $post    The post object being deleted.
	 * @return void
	 */
	public static function handle_product_delete( $post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! current_user_can( 'delete_posts' ) || ! $post_id ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$notification_ids = wc_bis_get_notifications(
			array(
				'product_id' => $post_id,
			)
		);

		if ( $notification_ids ) {
			foreach ( $notification_ids as $notification_id ) {
				WC_BIS()->db->notifications->delete( $notification_id );
			}
		}
	}

	/**
	 * Run daily for maintenance.
	 *
	 * @since 9.9.0
	 * @return void
	 */
	public static function do_wc_bis_daily() {

		// Delete overdue unverified notifications given a specified time threshold.
		$expiration_time_threshold = wc_bis_get_verification_expiration_time_threshold();
		$time_threshold            = wc_bis_get_delete_unverified_time_threshold();
		if ( 0 === $time_threshold ) {
			return;
		}

		$now                = time();
		$overdue_threshold  = $now - $expiration_time_threshold - $time_threshold;
		$overdue_query_args = array(
			'is_active'   => 'off',
			'is_verified' => 'no',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => 'awaiting_verification',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'key'     => '_verification_created_at',
					'value'   => $overdue_threshold,
					'compare' => '<',
					'type'    => 'UNSIGNED',
				),
			),
			'order_by'    => array( 'id' => 'DESC' ),
		);

		$overdue_notifications     = wc_bis_get_notifications( $overdue_query_args );
		$has_expired_notifications = ! empty( $overdue_notifications );

		if ( $has_expired_notifications ) {

			foreach ( $overdue_notifications as $notification ) {

				if ( $notification->is_active() || ! $notification->is_expired() || (int) $notification->get_meta( '_verification_created_at' ) > $overdue_threshold ) {
					continue;
				}

				// Force delete.
				$notification->delete();
			}
		}
	}

	/**
	 * Auto-remove our notices after they are displayed.
	 *
	 * @since 9.9.0
	 * @return void
	 */
	public static function auto_remove_notices() {
		$notices = WC_Admin_Notices::get_notices();

		foreach ( $notices as $notice_id => $notice ) {
			if ( 0 === strpos( $notice, 'bis_spam_notice_' ) || 0 === strpos( $notice, 'bis_queue_notice_' ) ) {
				WC_Admin_Notices::remove_notice( $notice, true );
			}
		}
	}
}

WC_BIS_Sync_Tasks::init();
