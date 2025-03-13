<?php
/**
 * WC_BIS_Admin class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

use Automattic\Jetpack\Constants;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class.
 *
 * Loads admin scripts, includes admin classes and adds admin hooks.
 *
 * @class    WC_BIS_Admin
 * @version  2.0.2
 */
class WC_BIS_Admin {

	/**
	 * Setup Admin class.
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'admin_init' ) );

		// Add a message in the WP Privacy Policy Guide page.
		add_action( 'admin_init', array( __CLASS__, 'add_privacy_policy_guide_content' ) );

		// Settings.
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_resources' ), 11 );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_wc_screens' ) );

		// Inject notices into variation's metabox validation.
		add_filter( 'woocommerce_show_invalid_variations_notice', array( __CLASS__, 'inject_custom_notices' ) );

		// Display and save product-level stock notifications option.
		add_action( 'woocommerce_product_options_stock_status', array( __CLASS__, 'add_disable_bis_checkbox' ), 20 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_product_object' ) );

		// Prepare notices.
		add_action( 'admin_notices', array( __CLASS__, 'maybe_add_active_notifications_notice' ), 0 );

		// Handle bulk admin deactivation.
		add_action( 'admin_init', array( __CLASS__, 'process_bulk_admin_deactivate' ) );
	}

	/**
	 * Admin init.
	 */
	public static function admin_init() {
		self::includes();
	}

	/**
	 * Inclusions.
	 */
	protected static function includes() {

		// Admin Menus.
		require_once WC_ABSPATH . 'includes/admin/class-wc-bis-admin-menus.php';

		// Export.
		require_once WC_ABSPATH . 'includes/admin/class-wc-bis-admin-exporters.php';

		// Reports.
		// require_once  WC_ABSPATH . 'includes/admin/reports/class-wc-bis-admin-reports.php' ;

		// Admin AJAX.
		require_once WC_ABSPATH . 'includes/admin/class-wc-bis-admin-ajax.php';
	}

	/**
	 * Register own version of select2 library.
	 *
	 * @return void
	 */
	public static function maybe_register_selectsw() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
	}

	/**
	 * Load own version of select2 library.
	 *
	 * @return void
	 */
	public static function maybe_load_selectsw() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
	}

	/**
	 * Display notice when selectSW library is unsupported.
	 *
	 * @return void
	 */
	public static function maybe_display_selectsw_notice() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
	}

	/**
	 * Add a message in the WP Privacy Policy Guide page.
	 *
	 * @return void
	 */
	public static function add_privacy_policy_guide_content() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( 'WooCommerce Back In Stock Notifications', self::get_privacy_policy_guide_message() );
		}
	}

	/**
	 * Message to add in the WP Privacy Policy Guide page.
	 *
	 * @return string
	 */
	protected static function get_privacy_policy_guide_message() {

		$content = '
			<div class="wp-suggested-text">' .
				'<p class="privacy-policy-tutorial">' .
					__( 'WooCommerce Back In Stock Notifications stores the following information when customers sign up to receive back-in-stock notifications:', 'woocommerce' ) .
				'</p>' .
				'<ul class="privacy-policy-tutorial">' .
					'<li>' . __( 'Customer e-mail.', 'woocommerce' ) . '</li>' .
					'<li>' . __( 'Sign-up date.', 'woocommerce' ) . '</li>' .
					'<li>' . __( 'Notification date.', 'woocommerce' ) . '</li>' .
				'</ul>' .
				'<p class="privacy-policy-tutorial">' .
					__( 'This information can be used to personally identify customers, and is stored in the database indefinitely.', 'woocommerce' ) .
				'</p>' .
			'</div>';

		return $content;
	}

	/**
	 * Add 'Stock Notifications' tab to WooCommerce Settings tabs.
	 *
	 * @param  array $settings
	 * @return array $settings
	 */
	public static function add_settings_page( $settings ) {

		$settings[] = include 'settings/class-wc-bis-admin-settings.php';

		return $settings;
	}

	/**
	 * Admin scripts.
	 *
	 * @return void
	 */
	public static function admin_resources() {

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );

		wp_register_style( 'wc-bis-admin', WC()->plugin_url() . '/assets/css/bis-admin.css', array(), $version );
		wp_style_add_data( 'wc-bis-admin', 'rtl', 'replace' );

		wp_register_script( 'wc-bis-writepanel', WC()->plugin_url() . '/assets/js/admin/wc-bis-admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'wp-util', 'wc-backbone-modal' ), $version );

		$params = array(
			'wc_ajax_url'                               => admin_url( 'admin-ajax.php' ),
			'i18n_wc_delete_notification_warning'       => __( 'Delete this notification permanently?', 'woocommerce' ),
			'i18n_wc_bulk_delete_notifications_warning' => __( 'Delete the selected notifications permanently?', 'woocommerce' ),
			// Export modal.
			'modal_export_notifications_nonce'          => wp_create_nonce( 'wc-bis-modal-notifications-export' ),
			'export_notifications_nonce'                => wp_create_nonce( 'wc-bis-notifications-export' ),
			'new_notification_product_data_nonce'       => wp_create_nonce( 'wc-bis-new-notification-product-data' ),
			'i18n_export_modal_title'                   => __( 'Export Notifications', 'woocommerce' ),
			// Dashboard.
			'dashboard_most_subscribed_date_range'      => wp_create_nonce( 'wc-bis-most-subscribed-date-range' ),
			'i18n_dashboard_table_no_results'           => __( 'No data recorded.', 'woocommerce' ),
			/* translators: notifications count, date */
			'i18n_dashboard_sign_up_chart_tooltip'      => __( '%notifications% signed up on %date%', 'woocommerce' ),
			/* translators: notifications count, date */
			'i18n_dashboard_sent_chart_tooltip'         => __( '%notifications% sent on %date%', 'woocommerce' ),
		);

		wp_register_script( 'wc-bis-dashboard', WC()->plugin_url() . '/assets/js/admin/wc-bis-admin-dashboard' . $suffix . '.js', array( 'jquery', 'wc-bis-writepanel' ), $version );

		/*
		 * Enqueue specific styles & scripts.
		 */
		if ( WC_BIS()->is_current_screen( array( 'woocommerce_page_wc-status', 'woocommerce_page_wc-settings' ) ) ) {
			wp_enqueue_script( 'wc-bis-writepanel' );

			if ( WC_BIS()->is_dashboard() ) {
				wp_enqueue_script( 'wc-bis-dashboard' );
				wp_enqueue_script( 'flot' );
				wp_enqueue_script( 'flot-resize' );
				wp_enqueue_script( 'flot-time' );
			}

			wp_localize_script( 'wc-bis-writepanel', 'wc_bis_admin_params', $params );

			wp_enqueue_style( 'wc-bis-admin' );
		}
	}

	/**
	 * Add PB debug data in the system status.
	 *
	 * @return void
	 */
	public static function render_system_status_items() {
		wc_deprecated_function( __METHOD__, '9.9.0' );
	}

	/**
	 * Add screen ids.
	 *
	 * @return void
	 */
	public static function add_wc_screens( $screens ) {
		$screens = array_merge( $screens, WC_BIS()->get_screen_ids() );
		return $screens;
	}

	/**
	 * Inject custom notices into variation's metabox.
	 *
	 * @param  bool $show_invalid_variations_notice
	 * @return bool
	 */
	public static function inject_custom_notices( $show_invalid_variations_notice ) {
		WC_BIS_Admin_Notices::output_notices();
		return $show_invalid_variations_notice;
	}

	/**
	 * Setting to allow admins disabling bis on product level.
	 *
	 * @since  1.2.0
	 *
	 * @return void
	 */
	public static function add_disable_bis_checkbox() {

		global $product_object;
		if ( ! is_a( $product_object, 'WC_Product' ) ) {
			return;
		}

		$bis_enabled = 'yes' === $product_object->get_meta( '_wc_bis_disabled', 'no' ) ? 'no' : 'yes';

		wp_nonce_field( 'woocommerce-bis-edit-product', 'bis_edit_product_security' );

		if ( 'yes' === get_option( 'wc_bis_allow_signups', 'yes' ) ) {
			woocommerce_wp_checkbox(
				array(
					'id'            => '_wc_bis_enabled',
					'label'         => __( 'Stock notifications', 'woocommerce' ),
					'value'         => $bis_enabled,
					'wrapper_class' => implode(
						' ',
						array_map(
							function ( $type ) {
								return 'show_if_' . $type; },
							wc_bis_get_supported_types()
						)
					) . ' hide_if_composite',
					'description'   => __( 'Let customers sign up to be notified when this product is restocked', 'woocommerce' ),
				)
			);
			return;
		}
		?>
		<div id="message" class="inline notice woocommerce-message">
		<?php
			$info_img_url = WC_ADMIN_IMAGES_FOLDER_URL . '/icons/info.svg';
		?>
			<img class="info-icon" src="<?php echo esc_url( $info_img_url ); ?>" /><p>
					<?php
						/* translators: Settings page for Back in Stock Notifications */
						echo wp_kses_post( 
							sprintf( 
								__( 'Sign-ups for stock notifications are disabled for all products in the store. To control sign-ups for this product, first enable the global <a href="%s">"Allow sign-ups"</a> option.', 'woocommerce' ), 
								esc_url( admin_url( 'admin.php?page=wc-settings&tab=products&section=bis_settings' ) )
								)
							);
					?>
				</p>
			</div>
		<?php
	}

	/**
	 * Save product settings meta.
	 *
	 * @since  1.2.0
	 *
	 * @param  WC_Product $product
	 * @return void
	 */
	public static function process_product_object( $product ) {

		check_admin_referer( 'woocommerce-bis-edit-product', 'bis_edit_product_security' );

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( ! $product->is_type( wc_bis_get_supported_types() ) ) {
			$product->delete_meta_data( '_wc_bis_disabled' );
			return;
		}

		$posted_bis_enabled = isset( $_POST['_wc_bis_enabled'] ); // @phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $posted_bis_enabled ) {
			$product->add_meta_data( '_wc_bis_disabled', 'yes' );
		} else {
			$product->delete_meta_data( '_wc_bis_disabled' );
		}
	}

	/**
	 * Add a notice if there are active notifications and the registration form is disabled or the product is unpublished.
	 *
	 * @since  1.2.0
	 *
	 * @return void
	 */
	public static function maybe_add_active_notifications_notice() {

		global $post_id;
		if ( ! $post_id ) {
			return;
		}

		// Get admin screen ID.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'product' !== $screen_id ) {
			return;
		}

		$product_id   = $post_id;
		$product_type = WC_Product_Factory::get_product_type( $product_id );
		if ( ! in_array( $product_type, wc_bis_get_supported_types(), true ) ) {
			return;
		}

		$product_ids = array( $product_id );
		$product     = wc_get_product( $product_id );
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		// Bail early.
		if ( 'yes' !== $product->get_meta( '_wc_bis_disabled', true ) && 'publish' === $product->get_status() ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			$product_ids = array_merge( $product_ids, $product->get_children() );
		}

		// Count existing and active notifications.
		$notifications_count = wc_bis_get_notifications_count( $product_ids, true );
		if ( ! $notifications_count ) {
			return;
		}

		// Build CTA link.
		$bulk_deactivate_url = wp_nonce_url( add_query_arg( array( 'wc_bis_admin_bulk_deactivate' => $product_id ) ), 'wc_bis_admin_bulk_deactivate_' . $product_id );

		// Build notices.
		if ( 'publish' !== $product->get_status() ) {

			$notice = sprintf(
				// translators: placeholder %1$s: the number of active back in stock notifications, placeholder %2$s: the deactivation URL.
				_n(
					'This product is not published. However, %1$s customer has signed up to be notified when this product is restocked. If you do not intend to publish or restock this product in the future, click <a href="%2$s" class="js_wc_bis_notice_confirm_deactivate">here</a> to deactivate that notification.',
					'This product is not published. However, %1$s customers have signed up to be notified when this product is restocked. If you do not intend to publish or restock this product in the future, click <a href="%2$s" class="js_wc_bis_notice_confirm_deactivate"> to deactivate those notifications.</a>.',
					$notifications_count,
					'woocommerce'
				),
				number_format_i18n( $notifications_count ),
				$bulk_deactivate_url
			);

			WC_BIS_Admin_Notices::add_notice( $notice, 'warning' );

		} elseif ( 'yes' === get_post_meta( $product_id, '_wc_bis_disabled', true ) ) {

			$notice = sprintf(
				// translators: %1$s the number of active back in stock notifications, %2$s the deactivation URL.
				_n(
					'Stock notifications for this product are currently disabled under <strong>Product Data > Inventory > Stock Notifications</strong>. However, %1$s notification is scheduled to be sent when this product is restocked. To deactivate it, click <a href="%2$s" class="js_wc_bis_notice_confirm_deactivate">here</a>.',
					'Stock notifications for this product are currently disabled under <strong>Product Data > Inventory > Stock Notifications</strong>. However, %1$s notifications are scheduled to be sent when this product is restocked. To deactivate them, click <a href="%2$s" class="js_wc_bis_notice_confirm_deactivate">here</a>.',
					$notifications_count,
					'woocommerce'
				),
				number_format_i18n( $notifications_count ),
				$bulk_deactivate_url
			);

			WC_BIS_Admin_Notices::add_notice( $notice, 'warning' );
		}

		$confirmation = __( 'This action cannot be undone. Continue?', 'woocommerce' );
		?>

		<script type="text/javascript">
			jQuery( document ).on( 'click', '.js_wc_bis_notice_confirm_deactivate', function() {
				return confirm( '<?php echo esc_html( $confirmation ); ?>' );
			} );
		</script>

		<?php
	}

	/**
	 * Handle bulk deactivation in admin context and refresh the page to reveal notice.
	 *
	 * @since  1.2.0
	 *
	 * @return void
	 */
	public static function process_bulk_admin_deactivate() {

		if ( ! isset( $_GET['wc_bis_admin_bulk_deactivate'], $_GET['post'] ) ) {
			return;
		}

		$url        = remove_query_arg( array( 'wc_bis_admin_bulk_deactivate', '_wpnonce' ) );
		$product_id = absint( wc_clean( $_GET['wc_bis_admin_bulk_deactivate'] ) );
		check_admin_referer( 'wc_bis_admin_bulk_deactivate_' . $product_id );

		$updated = self::handle_bulk_admin_deactivation( $product_id );

		if ( $updated > 0 ) {

			$notice = sprintf(
			// translators: placeholder 1 is the number of deactivated notifications.
				_n(
					'%1$s notification deactivated.',
					'%1$s notifications deactivated.',
					$updated,
					'woocommerce'
				),
				number_format_i18n( $updated )
			);

			WC_BIS_Admin_Notices::add_notice( $notice, 'success', true );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Bulk deactivate notifications.
	 *
	 * @since  1.7.0
	 *
	 * @param  $product_id  integer
	 * @return bool
	 */
	public static function handle_bulk_admin_deactivation( $product_id ) {

		$updated     = 0;
		$product_ids = array( $product_id );
		$product     = wc_get_product( $product_id );

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $updated;
		}

		if ( $product->is_type( 'variable' ) ) {
			$product_ids = array_merge( $product_ids, $product->get_children() );
		}

		// Let's get all the notification IDs for this product.
		$query_args       = array(
			'return'     => 'ids',
			'product_id' => $product_ids,
			'is_active'  => 'on',
		);
		$notification_ids = wc_bis_get_notifications( $query_args );

		if ( ! $notification_ids ) {
			return $updated;
		}

		foreach ( $notification_ids as $notification_id ) {

			$notification = wc_bis_get_notification( $notification_id );
			if ( $notification ) {

				$notification->set_active( 'off' );
				if ( $notification->save() ) {
					++$updated;
				}

				$notification->add_event( 'deactivated', wp_get_current_user() );
			}
		}

		return $updated;
	}
}

WC_BIS_Admin::init();
