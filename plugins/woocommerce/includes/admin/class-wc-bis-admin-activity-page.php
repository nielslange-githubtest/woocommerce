<?php
declare( strict_types=1 );

/**
 * WC_BIS_Admin_Activity_Page class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_BIS_Admin_Activity_Page Class.
 */
class WC_BIS_Admin_Activity_Page {

	/**
	 * Page home URL.
	 *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=wc-status&tab=bis_activity';

	/**
	 * Render page.
	 */
	public static function output() {

		// Verify nonce if search is being performed.
		if ( isset( $_REQUEST['s'] ) ) {
			check_admin_referer( 'bulk-activities' );
		}

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$table  = new WC_BIS_Activity_List_Table();
		$table->prepare_items();

		include __DIR__ . '/views/html-admin-activity.php';
	}
}
