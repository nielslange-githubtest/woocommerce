<?php
/**
 * WC_BIS_DB class
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
 * DB API class.
 */
class WC_BIS_DB {

	/**
	 * A reference to the DB Model - @see WC_BIS_Notifications_DB.
	 *
	 * @var WC_BIS_Notifications_DB
	 */
	public $notifications;

	/**
	 * A reference to the DB Model - @see WC_BIS_Activity_DB.
	 *
	 * @var WC_BIS_Activity_DB
	 */
	public $activity;

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Foul!', 'woocommerce' ), '1.0.0' );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Attach DB Models to public properties.
		$this->notifications = new WC_BIS_Notifications_DB();
		$this->activity      = new WC_BIS_Activity_DB();

		add_action( 'init', array( $this, 'notifications_table_fix' ), 0 );
		add_action( 'switch_blog', array( $this, 'notifications_table_fix' ), 0 );
	}

	/**
	 * Make WP see 'bis_notification_meta' as a meta type.
	 */
	public function notifications_table_fix() {
		global $wpdb;
		$wpdb->bis_notificationsmeta = $wpdb->prefix . 'woocommerce_bis_notificationsmeta';
		$wpdb->tables[]              = 'woocommerce_bis_notificationsmeta';
	}
}
