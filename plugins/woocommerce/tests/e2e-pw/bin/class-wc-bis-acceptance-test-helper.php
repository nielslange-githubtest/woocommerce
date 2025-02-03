<?php //phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * Helper class for BIS acceptance tests.
 *
 * @class   WC_BIS_Acceptance_Test_Helper
 * @package Automattic\WooCommerce\E2EPlaywright
 */

/**
 * Class WC_BIS_Acceptance_Test_Helper
 */
class WC_BIS_Acceptance_Test_Helper {

	/**
	 * Default settings.
	 * @var array
	 */
	protected static $default_settings = array(
		'wc_bis_account_required'                   => 'no',
		'wc_bis_opt_in_required'                    => 'no',
		'wc_bis_double_opt_in_required'             => 'no',
		'wc_bis_create_new_account_on_registration' => 'no',
		'wc_bis_show_product_registrations_count'   => 'no',
		'wc_bis_loop_signup_prompt_status'          => 'no',
	);

	/**
	 * Add Hooks.
	 */
	public static function init() {

		// Handle requests.
		add_action( 'woocommerce_init', array( __CLASS__, 'handle_request' ), 100 );

		// Register shortcodes for viewing confirmation emails.
		add_action( 'plugins_loaded', array( __CLASS__, 'register_shortcodes' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Check if user is admin
	 * @return bool
	 */
	public static function is_allowed() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Register REST routes.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'wc/v3',
			'/create-bis-notifications',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_notifications' ),
				'permission_callback' => array( __CLASS__, 'is_allowed' ),
				'args'                => array(
					'product_id' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/*
	 * Functions.
	 */
	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	protected static function is_request( $type ) {

		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}


	/*
	 * Callbacks.
	 */

	/**
	 * Handles requests to interact with the back-end.
	 *
	 * @return  void
	 */
	public static function handle_request() {

		if ( ! self::is_request( 'frontend' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['process_notifications'] ) ) {

			$action_id = ActionScheduler::store()->query_action(
				array(
					'hook' => 'wc_bis_process_notifications_batch',
				)
			);

			if ( $action_id ) {
				$runner = ActionScheduler_QueueRunner::instance();
				$runner->process_action( $action_id, 'E2E Tester' );
			}
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['process_outofstock_products'] ) ) {

			$action_id = ActionScheduler::store()->query_action(
				array(
					'hook' => 'wc_bis_process_outofstock_products',
				)
			);

			if ( $action_id ) {
				$runner = ActionScheduler_QueueRunner::instance();
				$runner->process_action( $action_id, 'E2E Tester' );
			}
		}
	}

	/**
	 * Registers shortcodes for viewing BIS e-mail contents.
	 *
	 * @return  void
	 */
	public static function register_shortcodes() {
		add_shortcode( 'confirmation_received_email', array( __CLASS__, 'render_confirmation_received_email_shortcode' ) );
		add_shortcode( 'notification_received_email', array( __CLASS__, 'render_notification_received_email_shortcode' ) );
		add_shortcode( 'verify_received_email', array( __CLASS__, 'render_verification_received_email_shortcode' ) );
	}

	/**
	 * Renders the 'confirmation_received_email' shortcode.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_confirmation_received_email_shortcode( $atts ) {

		$args = shortcode_atts(
			array(
				'notification_id' => '',
			),
			$atts
		);

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$notification_id = ! empty( $_GET['notification_id'] ) ? intval( $_GET['notification_id'] ) : intval( $args['notification_id'] );

		if ( ! $notification_id ) {
			return;
		}

		$notification = new WC_BIS_Notification_Data( $notification_id );

		if ( ! $notification->get_id() ) {
			return;
		}

		$emails = new WC_Emails();
		$emails->init();

		$email = new WC_BIS_Email_Notification_Confirm();

		$email->object = $notification;
		$email->set_placeholders_value();

		ob_start();
		echo esc_html( '<h2 class="bis-confirm-email-title">' . $email->get_subject() . '</h2>' );
		WC_BIS()->emails->confirm_notification_email_html( $notification, $email );
		$content = ob_get_clean();

		return '<div class="bis-confirm-email-content">' . $content . '</div>';
	}

	/**
	 * Renders the 'notification_received_email' shortcode.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_notification_received_email_shortcode( $atts ) {

		$args = shortcode_atts(
			array(
				'notification_id' => '',
			),
			$atts
		);

		$notification_id = ! empty( $_GET['notification_id'] ) ? intval( $_GET['notification_id'] ) : intval( $args['notification_id'] );

		if ( ! $notification_id ) {
			return;
		}

		$notification = new WC_BIS_Notification_Data( $notification_id );

		if ( ! $notification->get_id() ) {
			return;
		}

		$emails = new WC_Emails();
		$emails->init();

		$email = new WC_BIS_Email_Notification_Received();

		$email->object = $notification;
		$email->set_placeholders_value();

		ob_start();
		echo esc_html( '<h2 class="bis-notify-email-title">' . $email->get_subject() . '</h2>' );
		WC_BIS()->emails->notification_email_html( $notification, $email );
		$content = ob_get_clean();

		return '<div class="bis-notify-email-content">' . $content . '</div>';
	}

	/**
	 * Renders the 'verification_received_email' shortcode.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_verification_received_email_shortcode( $atts ) {

		$args = shortcode_atts(
			array(
				'notification_id' => '',
			),
			$atts
		);

		$notification_id = ! empty( $_GET['notification_id'] ) ? intval( $_GET['notification_id'] ) : intval( $args['notification_id'] );

		if ( ! $notification_id ) {
			return;
		}

		$notification = new WC_BIS_Notification_Data( $notification_id );

		if ( ! $notification->get_id() ) {
			return;
		}

		$emails = new WC_Emails();
		$emails->init();

		$email = new WC_BIS_Email_Notification_Verify();

		$email->object = $notification;
		$email->set_placeholders_value();

		ob_start();
		echo esc_html( '<h2 class="bis-verify-email-title">' . $email->get_subject() . '</h2>' );
		WC_BIS()->emails->verify_notification_email_html( $notification, $email );
		$content = ob_get_clean();

		return '<div class="bis-verify-email-content">' . $content . '</div>';
	}

	/**
	 * Creates some notifications that are required for tests.
	 *
	 * @param  array $request Request data.
	 *
	 */
	public static function create_notifications( $request ) {

		$notification = new WC_BIS_Notification_Data(
			array(
				'type'               => 'one-time',
				'product_id'         => $request['product_id'],
				'user_id'            => 1,
				'user_email'         => 'customer@woocommercecoree2etestsuite.com',
				'create_date'        => 0,
				'last_notified_date' => 0,
				'subscribe_date'     => 0,
				'is_queued'          => 'off',
				'is_active'          => 'off',
				'is_verified'        => 'yes',
			)
		);

		$notification->save();

		wp_send_json_success(
			$notification->get_id()
		);
	}

	/*
	 * Helpers.
	 */

	/**
	 * Log using 'WC_Logger' class.
	 *
	 * @param  string $message Message to log.
	 * @param  string $level  Log level.
	 * @param  string $context Context.
	 */
	public static function log( $message, $level, $context ) {
		$logger = wc_get_logger();
		$logger->log( $level, $message, array( 'source' => $context ) );
	}
}

SW_BIS_Acceptance_Test_Helper::init();
