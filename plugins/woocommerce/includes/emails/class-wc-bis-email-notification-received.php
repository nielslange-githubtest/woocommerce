<?php
/**
 * WC_BIS_Email_Notification_Received class
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    9.9.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_BIS_Email_Notification_Received', false ) ) :

	include_once __DIR__ . '/interface-wc-bis-email-previewable.php';

	/**
	 * Notification Received email controller.
	 *
	 * @class    WC_BIS_Email_Notification_Received
	 * @version  9.9.0
	 */
	class WC_BIS_Email_Notification_Received extends WC_Email implements WC_BIS_Email_Previewable {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'bis_notification_received';
			$this->customer_email = true;

			$this->title       = __( 'Back in stock notification', 'woocommerce' );
			$this->description = __( 'Email sent to signed-up customers when a product is back in stock.', 'woocommerce' );

			$this->template_html  = 'emails/back-in-stock-notification-received.php';
			$this->template_plain = 'emails/plain/back-in-stock-notification-received.php';

			$this->setup_placeholders();

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Prepares the email based on the notification data.
		 *
		 * @since 9.9.0
		 * @param WC_BIS_Notification_Data $notification Notification.
		 *
		 * @return void
		 */
		public function prepare_email( WC_BIS_Notification_Data $notification ): void {
			$this->object                         = $notification;
			$this->recipient                      = $notification->get_user_email();
			$product                              = $notification->get_product();
			$this->placeholders['{product_name}'] = preg_replace( $this->plain_search, $this->plain_replace, $product->get_name() );
			$this->placeholders['{site_title}']   = preg_replace( $this->plain_search, $this->plain_replace, $this->get_blogname() );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param WC_BIS_Notification_Data|int $notification Notification.
		 *
		 * @return void
		 */
		public function trigger( $notification ) {
			$this->setup_locale();

			if ( is_numeric( $notification ) ) {
				$notification = wc_bis_get_notification( $notification );
			}

			if ( ! is_a( $notification, 'WC_BIS_Notification_Data' ) ) {
				return;
			}

			$this->maybe_switch_locale( $notification );

			$this->prepare_email( $notification );
			$product = $notification->get_product();

			// Sanity check notification.
			if ( ! $notification->is_active() || ! $notification->is_verified() || ! $product->is_in_stock() ) {

				try {
					$notification->set_queued_status( 'off' );
					$notification->save();
					$notification->add_event( 'aborted' );
				} catch ( Exception $e ) {
					wc_get_logger()->error( $e->getMessage(), array( 'source' => 'wc_bis_logs' ) );
				}

				return;
			}

			// Check product status.
			$product_is_unpublished = false;
			if ( 'publish' !== $product->get_status() ) {
				$product_is_unpublished = true;
			}

			if ( ! $product_is_unpublished && $product->is_type( 'variation' ) && 'publish' !== get_post_status( $product->get_parent_id() ) ) {
				$product_is_unpublished = true;
			}

			if ( $product_is_unpublished ) {

				$user          = $notification->get_user_id() ? get_user_by( 'id', $notification->get_user_id() ) : false;
				$allowed_roles = array( 'manage_woocommerce', 'administrator' );

				if ( ! is_a( $user, 'WP_User' ) || ! array_intersect( $allowed_roles, $user->roles ) ) {

					try {
						$notification->set_queued_status( 'off' );
						$notification->save();
						$notification->add_event( 'aborted' );
					} catch ( Exception $e ) {
						wc_get_logger()->error( $e->getMessage(), array( 'source' => 'wc_bis_logs' ) );
					}

					return;
				}
			}

			// Check if notification is manually sent.
			if ( $notification->is_delivered() ) {
				return;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {

				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				$this->maybe_restore_locale( $notification );

				try {
					$notification->set_last_notified_date( time() );
					$notification->add_event( 'delivered' );

					$notification->deactivate( 0 ); // 0: To set system.
					$notification->save();

				} catch ( Exception $e ) {
					wc_get_logger()->error( $e->getMessage(), array( 'source' => 'wc_bis_logs' ) );
				}
			}

			$this->restore_locale();
		}

		/**
		 * Switch locale if necessary based on notification meta.
		 *
		 * @since 3.0.1
		 *
		 * @param WC_BIS_Notification_Data $notification Notification object.
		 */
		private function maybe_switch_locale( $notification ) {
			$customer_locale = $notification->get_meta( '_customer_locale' );
			if ( ! empty( $customer_locale ) ) {
				switch_to_locale( $customer_locale );
			}
		}

		/**
		 * Restore locale if previously switched.
		 *
		 * @since 3.0.1
		 *
		 * @param WC_BIS_Notification_Data $notification Notification object.
		 */
		private function maybe_restore_locale( $notification ) {
			$customer_locale = $notification->get_meta( '_customer_locale' );
			if ( ! empty( $customer_locale ) ) {
				restore_previous_locale();
			}
		}

		/**
		 * Force trigger the sending of this email.
		 *
		 * @param WC_BIS_Notification_Data|int $notification Notification.
		 *
		 * @return void
		 */
		public function force_trigger( $notification ) {
			$this->setup_locale();

			if ( is_numeric( $notification ) ) {
				$notification = wc_bis_get_notification( $notification );
			}

			if ( ! is_a( $notification, 'WC_BIS_Notification_Data' ) ) {
				return;
			}

			$this->maybe_switch_locale( $notification );

			$this->object    = $notification;
			$this->recipient = $notification->get_user_email();
			$product         = $notification->get_product();
			$this->set_placeholders_value();

			// Sanity check notification.
			if ( ! $notification->is_active() || ! $notification->is_verified() || ! $product->is_in_stock() ) {

				try {
					$notification->set_queued_status( 'off' );
					$notification->save();
					$notification->add_event( 'aborted' );
				} catch ( Exception $e ) {
					wc_get_logger()->error( $e->getMessage(), array( 'source' => 'wc_bis_logs' ) );
				}

				return;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {

				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				$this->maybe_restore_locale( $notification );

				try {
					$notification->set_last_notified_date( time() );
					$notification->add_event( 'delivered', wp_get_current_user() );

					$notification->set_queued_status( 'off' );
					$notification->save();

				} catch ( Exception $e ) {
					wc_get_logger()->error( $e->getMessage(), array( 'source' => 'wc_bis_logs' ) );
				}
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '"{product_name}" is back in stock!', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'It\'s back in stock!', 'woocommerce' );
		}

		/**
		 * Get default email content.
		 *
		 * @return string
		 */
		public function get_default_intro_content() {
			return __( 'Great news: "{product_name}" is now available for purchase.', 'woocommerce' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for shopping with us.', 'woocommerce' );
		}

		/**
		 * Get email content.
		 *
		 * @return string
		 */
		public function get_into_content() {
			return $this->get_intro_content();
		}

		/**
		 * Get email content.
		 *
		 * @return string
		 */
		public function get_intro_content() {
			/**
			 * Filter: `woocommerce_bis_email_intro_content`.
			 *
			 * @since 9.9.0
			 *
			 * @param string $value The intro content.
			 */
			return apply_filters( 'woocommerce_bis_email_intro_content', $this->format_string( $this->get_option( 'intro_content', $this->get_default_intro_content() ) ), $this->object, $this );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {

			// Default template params.
			$template_args = array(
				'notification'       => $this->object,
				'product'            => $this->object->get_product(),
				'email_heading'      => $this->get_heading(),
				'intro_content'      => $this->get_intro_content(),
				'additional_content' => $this->get_additional_content(),
				'email'              => $this,
			);

			// Get the template.
			return wc_get_template_html(
				$this->template_html,
				$template_args
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'notification'       => $this->object,
					'product'            => $this->object->get_product(),
					'email_heading'      => $this->get_heading(),
					'intro_content'      => $this->get_intro_content(),
					'additional_content' => $this->get_additional_content(),
					'email'              => $this,
				)
			);
		}

		/**
		 * Setup placeholders.
		 *
		 * @since  1.3.0
		 */
		protected function setup_placeholders() {

			/**
			 * Filter: `woocommerce_bis_notification_email_placeholders`.
			 *
			 * @since 9.9.0
			 *
			 * @param string[] $placeholders The placeholders.
			 */
			$placeholder_keys = (array) apply_filters(
				'woocommerce_bis_notification_email_placeholders',
				array(
					'site_title',
					'product_name',
				)
			);

			$placeholders = array();
			foreach ( $placeholder_keys as $placeholder_key ) {
				$placeholders[ '{' . $placeholder_key . '}' ] = '';
			}

			$this->placeholders = $placeholders;
		}

		/**
		 * Set placeholders.
		 *
		 * @since  1.3.0
		 */
		public function set_placeholders_value() {
			$product = $this->object->get_product();

			$this->placeholders['{site_title}']   = preg_replace( $this->plain_search, $this->plain_replace, $this->get_blogname() );
			$this->placeholders['{product_name}'] = preg_replace( $this->plain_search, $this->plain_replace, $product->get_name() );

			foreach ( $this->placeholders as $key => $value ) {
				/**
				 * Filter: `woocommerce_bis_notification_email_placeholder_value`.
				 *
				 * @since 9.9.0
				 *
				 * @param string $value The placeholder value.
				 */
				$this->placeholders[ $key ] = apply_filters( 'woocommerce_bis_notification_email_placeholder_' . sanitize_title( $key ) . '_value', $value, $this->object );
			}
		}

		/**
		 * Initialize Settings Form Fields.
		 *
		 * @return void
		 */
		public function init_form_fields() {

			parent::init_form_fields();

			/* translators: %s: list of placeholders */
			$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

			$intro_content_field = array(
				'title'       => __( 'Email content', 'woocommerce' ),
				'description' => __( 'Text to appear below the main e-mail header.', 'woocommerce' ) . ' ' . $placeholder_text,
				'css'         => 'width: 400px; height: 75px;',
				'placeholder' => $this->get_default_intro_content(),
				'type'        => 'textarea',
				'desc_tip'    => true,
			);

			// Find `heading` key.
			$inject_index = array_search( 'heading', array_keys( $this->form_fields ), true );
			if ( $inject_index ) {
				++$inject_index;
			} else {
				$inject_index = 0;
			}

			// Inject.
			$this->form_fields = array_slice( $this->form_fields, 0, $inject_index, true ) + array( 'intro_content' => $intro_content_field ) + array_slice( $this->form_fields, $inject_index, count( $this->form_fields ) - $inject_index, true );
		}

		/**
		 * Setup action hooks.
		 *
		 * @since 1.2.0
		 *
		 * @return void
		 */
		public function setup_hooks() {
			add_action( 'woocommerce_bis_send_notification_to_customer_notification', array( $this, 'trigger' ), 10 );
			add_action( 'woocommerce_bis_force_send_notification_to_customer_notification', array( $this, 'force_trigger' ), 10 );
		}
	}

endif;

return new WC_BIS_Email_Notification_Received();
