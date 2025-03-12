<?php
/**
 * Back In Stock Notifications class file.
 */

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Packages;
use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Class to initiate Back In Stock Notifications functionality in core.
 */
class BackInStockNotifications {

	/**
	 * Database utility instance.
	 * 
	 * @var DatabaseUtil
	 */
	private static $db_utils;

	/**
	 * Whether this is an activation request for BIS.
	 * 
	 * @var bool
	 */
	private static $is_activation_request = false;

	/**
	 * Whether the standalone BIS plugin is active.
	 * 
	 * @var bool
	 */
	private static $bis_plugin_is_active = false;

	/**
	 * Option name for the feature flag.
	 * 
	 * @var string
	 */
	public static $ENABLE_OPTION_NAME = 'wc_feature_woocommerce_back_in_stock_notifications_enabled';

	/**
	 * Package name.
	 * 
	 * @var string
	 */
	public static $PACKAGE_NAME = 'woocommerce-back-in-stock-notifications';

	/**
	 * Prepare method that runs code irrespective of whether the feature is enabled or not.
	 * 
	 * These hooks need to be always registered to be able to react to changes in the feature flag
	 * changes via Settings UI.
	 *
	 * @internal
	 */
	public static function prepare_always() {
		// Enable/disable events when the feature flag is changed.
		add_action( 'update_option_wc_feature_woocommerce_back_in_stock_notifications_enabled', array( __CLASS__, 'maybe_update_bis_infrastructure' ), 10, 3 );
		add_action( 'add_option_wc_feature_woocommerce_back_in_stock_notifications_enabled', array( __CLASS__, 'handle_add_option' ), 10, 2 );
		add_action( 'delete_option_wc_feature_woocommerce_back_in_stock_notifications_enabled', array( __CLASS__, 'handle_delete_option' ), 10, 1 );

		// Add feature definition to WC > Settings > Advanced > Features page.
		add_action( 'woocommerce_register_feature_definitions', array( __CLASS__, 'add_feature_definition' ), 10, 1 );
	}

	/**	
	 * Add feature definition to settings page.
	 * 
	 * Note: By default, WC core adds options for all settings in WC_Install::create_options.
	 * This doesn't work for merged BIS, because the setting option is acting as an _override_
	 * for the rollout period flag. Thus, the feature definition won't be added during activation
	 * to skip creating the option.
	 * 
	 * This method copies the check in WC_Install::check_version, because the WC_INSTALLING
	 * constant is defined too late (init@5) while the feature definition is added in init@0
	 * during an update of WooCommerce core.
	 * 
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 * @internal
	 */
	public static function add_feature_definition( $features_controller ) {
		// Only add feature definition if WooCommerce is not currently being activated to avoid
		// adding the wc_feature_woocommerce_back_in_stock_notifications_enabled option during activation.
		if ( Constants::is_defined( 'WC_INSTALLING' ) ) {
			return;
		}

		$wc_version      = get_option( 'woocommerce_version' );
		$wc_code_version = WC()->version;
		$requires_update = version_compare( $wc_version, $wc_code_version, '<' );

		if ( ! Constants::is_defined( 'IFRAME_REQUEST' ) && $requires_update ) {
			return;
		}
	
		$definition = array(
			'name'               => __( 'Back in stock notifications', 'woocommerce' ),
			'description'        => self::is_enabled() ? 
				sprintf(
					__( 'Enable back in stock notifications for customers. Configure the options in <a href="%s">WooCommerce > Settings > Products > Customer stock notifications</a>.', 'woocommerce' ),
					esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=bis_settings' ) )
				) 
				: __(
					'Enable back in stock notifications for customers.',
					'woocommerce'
				),
			'enabled_by_default' => self::is_enabled_for_rollout(),
			'is_experimental'    => false,
			'is_legacy'          => true,
			'disable_ui'         => false,
			'option_key'         => self::$ENABLE_OPTION_NAME,
		);

		$features_controller->add_feature_definition(
			'back_in_stock_notifications',
			__( 'Back in stock notifications', 'woocommerce' ),
			$definition
		);
	
	}

	/**
	 * Class initialization
	 *
	 * @internal
	 */
	final public static function init() {

		// If this is an activation request for BIS, included code can't be loaded, as it will end up with a fatal error.
		if ( self::$is_activation_request || self::$bis_plugin_is_active ) { 
			return;
		}

		// Deactivate signups for BIS to prevent changing the single product screen.
		add_action( 'woocommerce_updated', array( __CLASS__, 'maybe_deactivate_signups' ) );

		// Include BIS files.
		include_once WC_ABSPATH . '/includes/bis/class-wc-back-in-stock.php';

		$wc_bis = wc_get_container()->get( LegacyProxy::class )->call_function( 'WC_BIS' );
		$wc_bis->initialize_plugin();
	}

	/**
	 * Deactivate signups for BIS to prevent changing the single product screen.
	 * 
	 * This should be called from the WooCommerce update hook.
	 * 
	 * If standalone BIS plugin was active, it won't change the signups option.
	 * If standalone BIS plugin was not active, it will deactivate signups to prevent 
	 * changing the single product screen.
	 * 
	 * @return void
	 */
	public static function maybe_deactivate_signups() {
		// Only run if BIS is enabled during the rollout period.
		if ( ! self::is_enabled() ) {
			return;
		}

		// Check if we've already done the initial setup.
		if ( 'yes' === get_option( 'wc_bis_core_initialized' ) ) {
			return;
		}	
	
		$option_value = get_option( 'wc_bis_allow_signups' );

		// BIS wasn't active at the time of the core update, => disable signups.
		if ( ! self::$bis_plugin_is_active ) {
			$option_value = 'no';
		}

		// New installation, enable signups.
		if ( 0 === array_sum( (array) wp_count_posts( 'product' ) ) ) {
			$option_value = 'yes';
		}

		if ( $option_value !== get_option( 'wc_bis_allow_signups' ) ) {
			update_option( 'wc_bis_allow_signups', $option_value );
		}

		// Mark as initialized so we don't run this again.
		update_option( 'wc_bis_core_initialized', 'yes' );
	}

	/**
	 * Returns true if the feature should be enabled for this WC instance during the rollout period.
	 *
	 * As of WooCommerce 9.9, Back In Stock Notifications will be merged, but disabled for all users.
	 * As of WooCommerce 10.0, Back In Stock Notifications will be enabled for 5% of users.
	 * As of WooCommerce 10.1, Back In Stock Notifications will be enabled for all users.
	 *
	 * Feature can be disabled via option `update_option( 'wc_feature_woocommerce_back_in_stock_notifications_enabled', 'no' )`,
	 * even when this method returns true.
	 *
	 * See also \Automattic\WooCommerce\Packages::get_enabled_packages.
	 *
	 * @return bool
	 */
	public static function is_enabled_for_rollout() {
		return false;
	}

	/**
	 * Returns true if the feature is enabled in this WC instance.
	 * 
	 * The user option takes precedence over the rollout period flag.
	 * 
	 * Helper method redirected to \Automattic\WooCommerce\Packages\Packages::is_package_enabled.
	 * 
	 * @return bool
	 */
	public static function is_enabled() {
		return Packages::is_package_enabled( self::$PACKAGE_NAME );
	}

	/**
	 * If WooCommerce Back In Stock Notifications gets activated forcibly, without WooCommerce active
	 * (e.g. via '--skip-plugins'), remove WooCommerce Back In Stock Notifications initialization functions
	 * early on in the 'plugins_loaded' timeline.
	 *
	 * This is called from \Automattic\WooCommerce\Packages::prepare_packages.
	 */
	public static function prepare() {

		// Set flag for activation request to prevent fatal errors when BIS is activated while WC+BIS is already active.
		$bis_plugin_name = 'woocommerce-back-in-stock-notifications/woocommerce-back-in-stock-notifications.php';

		// This needs to run after the standalone plugin is deactivated to restore the daily task.
		add_action( 'deactivate_' . $bis_plugin_name, array( __CLASS__, 'maybe_setup_events' ), 20 );
		
		// Cleanup events when WooCommerce is deactivated.
		add_action( 'deactivate_woocommerce/woocommerce.php', array( __CLASS__, 'cleanup_events' ), 20 );

		// Update BIS infrastructure after WooCommerce is installed.
		add_action( 'woocommerce_installed', array( __CLASS__, 'maybe_update_bis_infrastructure' ), 10, 1 );

		if ( function_exists( 'WC_BIS' ) ) {
			// This skips the initialization of BIS plugin to avoid duplicate code & fatal errors 
			// when standalone BIS plugin is active and WC core with BIS merged is loaded.
			// BIS plugin is then deactivated during plugins_loaded@10 priority from \Automattic\WooCommerce\Packages::on_init().
			remove_action( 'plugins_loaded', array( WC_BIS(), 'initialize_plugin' ), 9 );

			// When WC_BIS() is present before loading BIS from core, it will fatal during init(), 
			// so init() needs to be skipped. This should only be triggered once after 
			// a plugin update during the request when BIS plugin is deactivated.
			self::$bis_plugin_is_active = true;
		}		

		// Check for CLI activation via WP-CLI
		if ( defined('WP_CLI') && WP_CLI ) {
			global $argv;
			if ( is_array( $argv ) && in_array( 'plugin', $argv ) && in_array( 'activate', $argv ) && in_array( $bis_plugin_name, $argv ) ) {
				self::$is_activation_request = true;
				return;
			}
		}

		// Check for AJAX activation (network admin)
		if ( wp_doing_ajax() ) {
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate-plugin' ) {
				if ( isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] === $bis_plugin_name ) {
					self::$is_activation_request = true;
					return;
				}
			}
		}

		// Check for regular activation requests
		if ( isset( $_REQUEST['action'] ) ) {
			// Single plugin activation
			if ( $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) && $_REQUEST['plugin'] === $bis_plugin_name ) {
				self::$is_activation_request = true;
				return;
			}

			// Bulk plugin activation
			if ( $_REQUEST['action'] === 'activate-selected' && isset( $_REQUEST['checked'] ) && is_array( $_REQUEST['checked'] ) && in_array( $bis_plugin_name, $_REQUEST['checked'] ) ) {
				self::$is_activation_request = true;
				return;
			}
		}
	}

	/**
	 * Get BIS db schema.
	 * 
	 * @return string
	 */
	public static function get_bis_db_schema() : string {
		global $wpdb;
		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}
		$max_index_length = 191;
		$tables = "CREATE TABLE {$wpdb->prefix}woocommerce_bis_notifications (
  `id` BIGINT UNSIGNED NOT NULL auto_increment,
  `type` VARCHAR(128) default 'one-time' NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `user_email` VARCHAR($max_index_length) NOT NULL,
  `create_date` INT UNSIGNED default 0 NOT NULL,
  `subscribe_date` INT UNSIGNED default 0 NOT NULL,
  `last_notified_date` INT UNSIGNED default 0 NOT NULL,
  `is_queued` CHAR(3) default 'off' NOT NULL,
  `is_active` CHAR(3) default 'off' NOT NULL,
  `is_verified` CHAR(3) default 'yes' NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `user_email` (`user_email`),
  KEY `is_queued` (`is_queued`),
  KEY `is_active` (`is_active`),
  KEY `is_verified` (`is_verified`),
  KEY `idx_product_active_queue` (`product_id`,`is_active`,`is_queued`)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_bis_notificationsmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  bis_notifications_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar($max_index_length) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY bis_notifications_id (bis_notifications_id),
  KEY meta_key (meta_key($max_index_length))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_bis_activity (
  `id` BIGINT UNSIGNED NOT NULL auto_increment,
  `notification_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `user_email` VARCHAR(255) NOT NULL,
  `object_id` BIGINT UNSIGNED default 0 NOT NULL,
  `date` INT UNSIGNED NOT NULL,
  `note` text NULL,
  PRIMARY KEY  (`id`),
  KEY `notification_id` (`notification_id`),
  KEY `type` (`type`),
  KEY `user_id` (`user_id`)
) $collate;";
		return $tables;
	}
	/**
	 * Get BIS db schema if the feature is enabled. Otherwise, return an empty string.
	 * 
	 * @return string
	 */
	public static function maybe_get_bis_db_schema() : string {
		if ( ! self::is_enabled() ) {
			return '';
		}

		return self::get_bis_db_schema();


	}

	/**
	 * Check if BIS tables exist.
	 *
	 * Adapted from \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::check_orders_table_exists.
	 *
	 * @return bool
	 */
	public static function bis_tables_exist(): bool {


		self::$db_utils = wc_get_container()->get( DatabaseUtil::class );

		$missing_tables = self::$db_utils->get_missing_tables( self::maybe_get_bis_db_schema() );

		if ( 0 === count( $missing_tables ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create BIS db tables when feature is enabled after WC installation has run.
	 *
	 * Adapted from \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::create_database_tables.
	 *
	 * @return mixed
	 */
	public static function create_database_tables() {

		if ( ! isset( self::$db_utils ) ) {	
			self::$db_utils = wc_get_container()->get( DatabaseUtil::class );
		}

		$db_schema = self::maybe_get_bis_db_schema();
		self::$db_utils->dbdelta( $db_schema );
		$success = self::bis_tables_exist();
		if ( ! $success ) {
			$missing_tables = self::$db_utils->get_missing_tables( $db_schema );
			$missing_tables = implode( ', ', $missing_tables );
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
			$logger->error( "Back In Stock Notifications tables are missing in the database and couldn't be created. The missing tables are: $missing_tables" );
		}
		return $success;
	}

	/**
	 * Create BIS db tables when feature is enabled after WC installation has run.
	 * 
	 * This should be called from the feature flag change hook.
	 */
	public static function maybe_create_database_tables() {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( self::bis_tables_exist() ) {
			return;
		}

		return self::create_database_tables();
	}

	/**
	 * Handle the addition of the feature flag option.
	 * 
	 * This should be called from the feature flag change hook.
	 * 
	 * @param string $option The option name.
	 */
	public static function handle_add_option( $option, $new_value ) {
		if ( $option !== 'wc_feature_woocommerce_back_in_stock_notifications_enabled' ) {
			return;
		}

		self::maybe_update_bis_infrastructure( null, $new_value, $option );
	}

	/**
	 * Handle the deletion of the feature flag option.
	 * 
	 * This should be called from the feature flag change hook.
	 * 
	 * @param string $option The option name.
	 */
	public static function handle_delete_option( $option ) {
		if ( $option !== 'wc_feature_woocommerce_back_in_stock_notifications_enabled' ) {
			return;
		}

		// BIS is enabled by default, so if the option is deleted, it means it's being enabled.
		self::maybe_update_bis_infrastructure( null, 'yes', $option );
	}

	/**
	 * Setup BIS events when the feature flag is enabled.
	 * 
	 * This should be called from the feature flag change hook.
	 */
	public static function maybe_setup_events() {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( wp_next_scheduled( 'wc_bis_daily' ) ) {
			return;
		}

		if ( ! class_exists( 'WC_BIS_Install' ) ) {
			include_once WC_ABSPATH . '/includes/bis/class-wc-bis-install.php';
		}
		
		wc_get_container()->get( LegacyProxy::class )->call_static( 'WC_BIS_Install', 'create_events' );
	}

	/**
	 * Update BIS infrastructure when the feature flag is changed.
	 * 
	 * This should be called from the feature flag change hook.
	 * 
	 * @param string|null $old_value The old value of the option.
	 * @param string|null $new_value The new value of the option.
	 * @param string|null $option The option name.
	 */
	public static function maybe_update_bis_infrastructure( $old_value = null, $new_value = null, $option = null ) {
		// For option change, check if being disabled
		if ( isset( $old_value ) && isset( $new_value ) && $new_value === 'no' ) {
			self::cleanup_events();
			// Not cleaning up database tables to retain the data.
			return;
		}

		self::maybe_setup_events();
		self::maybe_create_database_tables();
	}

	/**
	 * Clean up scheduled BIS events when WooCommerce is deactivated.
	 * 
	 * This should be called from WooCommerce's deactivation hook or 
	 * when the BIS feature is disabled via the feature flag.
	 */
	public static function cleanup_events() {
		$timestamp = wp_next_scheduled( 'wc_bis_daily' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wc_bis_daily' );
		}
	}
}
