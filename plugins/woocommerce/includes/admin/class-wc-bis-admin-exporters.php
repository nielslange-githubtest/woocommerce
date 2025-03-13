<?php
declare( strict_types=1 );

/**
 * WC_BIS_Admin_Exporters class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_BIS_Admin_Exporters Class.
 *
 * @version 1.0.0
 */
class WC_BIS_Admin_Exporters {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! $this->export_allowed() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'download_export_file' ) );
		add_action( 'wp_ajax_woocommerce_bis_do_ajax_notifications_export', array( $this, 'bis_do_ajax_notifications_export' ) );
	}

	/**
	 * Return true if WooCommerce export is allowed for current user, false otherwise.
	 *
	 * @return bool Whether current user can perform export.
	 */
	protected function export_allowed() {
		return current_user_can( 'edit_products' ) && current_user_can( 'export' );
	}

	/**
	 * Serve the generated file.
	 */
	public function download_export_file() {
		if ( isset( $_GET['action'], $_GET['nonce'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_GET['nonce'] ) ), 'notification-csv' ) && 'download_notification_csv' === wc_clean( wp_unslash( $_GET['action'] ) ) ) {
			include_once WC_ABSPATH . 'includes/admin/export/class-wc-bis-notification-csv-exporter.php';
			$exporter = new WC_BIS_Notification_CSV_Exporter();

			if ( ! empty( $_GET['filename'] ) ) {
				$exporter->set_filename( wc_clean( wp_unslash( $_GET['filename'] ) ) );
			}

			$exporter->export();
		}
	}

	/**
	 * AJAX callback for doing the actual export to the CSV file.
	 */
	public function bis_do_ajax_notifications_export() {
		check_ajax_referer( 'wc-bis-notifications-export', 'security' );

		if ( ! $this->export_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient privileges to export notifications.', 'woocommerce' ) ) );
		}

		include_once WC_ABSPATH . 'includes/admin/export/class-wc-bis-notification-csv-exporter.php';

		$step     = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
		$exporter = new WC_BIS_Notification_CSV_Exporter();
		$exporter->set_column_names( $exporter->get_default_column_names() );
		$exporter->set_filters( $_POST );

		if ( ! empty( $_POST['export_meta'] ) ) {
			$exporter->enable_meta_export( true );
		}

		if ( ! empty( $_POST['filename'] ) ) {
			$exporter->set_filename( wc_clean( wp_unslash( $_POST['filename'] ) ) );
		}

		$exporter->set_page( $step );
		$exporter->generate_file();

		/**
		 * Filter: woocommerce_bis_export_get_ajax_query_args.
		 *
		 * @since 9.9.0
		 * @param array $query_args The query arguments for the export URL.
		 * @return array
		 */
		$query_args = apply_filters(
			'woocommerce_bis_export_get_ajax_query_args',
			array(
				'nonce'    => wp_create_nonce( 'notification-csv' ),
				'action'   => 'download_notification_csv',
				'filename' => $exporter->get_filename(),
			)
		);

		if ( 100 === $exporter->get_percent_complete() ) {
			wp_send_json_success(
				array(
					'step'       => 'done',
					'percentage' => 100,
					'url'        => add_query_arg( $query_args, admin_url( 'admin.php?page=bis_notifications' ) ),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'step'       => ++$step,
					'percentage' => $exporter->get_percent_complete(),
					'columns'    => $exporter->get_column_names(),
				)
			);
		}
	}
}

new WC_BIS_Admin_Exporters();
