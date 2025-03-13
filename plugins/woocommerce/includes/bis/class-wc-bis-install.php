<?php
/**
 * WC_BIS_Install class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles installation and updating tasks.
 *
 * @class    WC_BIS_Install
 * @version  x.x.x
 */
class WC_BIS_Install {

	/**
	 * Hook in.
	 */
	public static function init() {
		wc_deprecated_function( 'WC_BIS_Install::init', '9.9.0' );
		// TODO: Haven't ported the db upgrade when updating to 1.1.0, so perhaps add some info to folks on older BIS?
	}

	/**
	 * Init background updates.
	 */
	public static function init_background_updater() {
		wc_deprecated_function( 'WC_BIS_Install::init_background_updater', '9.9.0' );
		// All future db updates will be handled from within WC core.
	}

	/**
	 * Check version and run the installer if necessary.
	 */
	public static function maybe_install() {
		wc_deprecated_function( 'WC_BIS_Install::maybe_install', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Run the updater if triggered.
	 */
	public static function maybe_update() {
		wc_deprecated_function( 'WC_BIS_Install::maybe_update', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * If the DB version is out-of-date, a DB update must be in progress: define a 'WC_BIS_UPDATING' constant.
	 */
	public static function define_updating_constant() {
		wc_deprecated_function( 'WC_BIS_Install::define_updating_constant', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Install PB.
	 */
	public static function install() {
		wc_deprecated_function( 'WC_BIS_Install::install', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Schedule cron events.
	 */
	public static function create_events() {
		if ( ! wp_next_scheduled( 'wc_bis_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_bis_daily' );
		}
	}

	/**
	 * Is auto-updating enabled?
	 *
	 * @return boolean
	 */
	public static function auto_update_enabled() {
		wc_deprecated_function( 'WC_BIS_Install::auto_update_enabled', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * Trigger DB update.
	 */
	public static function trigger_update() {
		wc_deprecated_function( 'WC_BIS_Install::trigger_update', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Force re-start the update cron if everything else fails.
	 */
	public static function force_update() {
		wc_deprecated_function( 'WC_BIS_Install::force_update', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Updates plugin DB version when all updates have been processed.
	 */
	public static function update_complete() {
		wc_deprecated_function( 'WC_BIS_Install::update_complete', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * True if a DB update is pending.
	 *
	 * @return boolean
	 */
	public static function is_update_pending() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_pending', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * True if a DB update was started but not completed.
	 *
	 * @return boolean
	 */
	public static function is_update_incomplete() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_incomplete', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}


	/**
	 * True if a DB update is in progress.
	 *
	 * @return boolean
	 */
	public static function is_update_queued() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_queued', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * True if an update process is running.
	 *
	 * @return boolean
	 */
	public static function is_update_process_running() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_process_running', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * True if an update background process is running.
	 *
	 * @return boolean
	 */
	public static function is_update_background_process_running() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_background_process_running', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * True if a CLI update is running.
	 *
	 * @return boolean
	 */
	public static function is_update_cli_process_running() {
		wc_deprecated_function( 'WC_BIS_Install::is_update_cli_process_running', '9.9.0' );
		// Migrated to WC core's install routine.
		return false;
	}

	/**
	 * Update DB version to current.
	 *
	 * @param  string $version
	 */
	public static function update_db_version( $version = null ) {
		wc_deprecated_function( 'WC_BIS_Install::update_db_version', '9.9.0' );
		// Migrated to WC core's install routine.
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		wc_deprecated_function( 'WC_BIS_Install::get_db_update_callbacks', '9.9.0' );
		// Migrated to WC core's install routine.
		return array();
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param   mixed $links
	 * @param   mixed $file
	 * @return  array
	 */
	public static function plugin_row_meta( $links, $file ) {
		wc_deprecated_function( 'WC_BIS_Install::plugin_row_meta', '9.9.0' );
		// Migrated to WC core's install routine.
		return array();
	}
}
