<?php
/**
 * Plugin Name: WooCommerce POS
 * Plugin URI: https://woocommerce.com/products/woocommerce-pos/
 * Description: Point of Sale extension for WooCommerce.
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Text Domain: woocommerce-pos
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 *
 * @package WooCommerce\POS
 */

defined( 'ABSPATH' ) || exit;

// Define WC_POS_PLUGIN_FILE.
if ( ! defined( 'WC_POS_PLUGIN_FILE' ) ) {
	define( 'WC_POS_PLUGIN_FILE', __FILE__ );
}

// Define WC_POS_PLUGIN_DIR.
if ( ! defined( 'WC_POS_PLUGIN_DIR' ) ) {
	define( 'WC_POS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Define WC_POS_PLUGIN_URL.
if ( ! defined( 'WC_POS_PLUGIN_URL' ) ) {
	define( 'WC_POS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Main WooCommerce POS Class.
 */
class WC_POS {

	/**
	 * Plugin instance.
	 *
	 * @var WC_POS
	 */
	protected static $instance = null;

	/**
	 * Main WooCommerce POS Instance.
	 *
	 * Ensures only one instance of WooCommerce POS is loaded or can be loaded.
	 *
	 * @static
	 * @return WC_POS - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		$this->includes();
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 20 );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		// We'll load the email class only in the register_emails method
		// No need to include it here
	}

	/**
	 * Code to run on plugins loaded.
	 */
	public function on_plugins_loaded() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Load plugin text domain
		load_plugin_textdomain( 'woocommerce-pos', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
		// Include POS email classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
	}

	/**
	 * Register POS emails with WooCommerce.
	 *
	 * @param array $email_classes Email classes.
	 * @return array
	 */
	public function register_emails( $email_classes ) {
		// Include the email class file directly here
		// This ensures WooCommerce is fully loaded when we try to extend WC_Email
		require_once WC_POS_PLUGIN_DIR . 'includes/emails/class-wc-email-customer-pos-completed-order.php';

		// Register the email class
		$email_classes['WC_Email_Customer_POS_Completed_Order'] = new WC_Email_Customer_POS_Completed_Order();

		return $email_classes;
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( 
			/* translators: %s: WooCommerce URL */
			esc_html__( 'WooCommerce POS requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-pos' ), 
			'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' 
		) . '</p></div>';
	}
}

/**
 * Main instance of WooCommerce POS.
 *
 * Returns the main instance of WC_POS to prevent the need to use globals.
 *
 * @return WC_POS
 */
function WC_POS() {
	return WC_POS::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_pos'] = WC_POS(); 
