<?php
/**
 * Class WC_Email_Customer_POS_Completed_Order file.
 *
 * @package WooCommerce\POS\Emails
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include the base class
if ( ! class_exists( 'WC_Email_POS_Base', false ) ) {
	$base_file = WC_POS_PLUGIN_DIR . 'includes/emails/class-wc-email-pos-base.php';
	if ( file_exists( $base_file ) ) {
		require_once $base_file;
	} else {
		error_log( 'WooCommerce POS: Base email class file not found at: ' . $base_file );
	}
}

// Only define this class if the base class exists and we haven't defined it yet
if ( class_exists( 'WC_Email_POS_Base', false ) && ! class_exists( 'WC_Email_Customer_POS_Completed_Order', false ) ) :

	/**
	 * POS Order completed email.
	 *
	 * An email sent to the customer when a POS order is completed.
	 *
	 * @class       WC_Email_Customer_POS_Completed_Order
	 * @version     1.0.0
	 * @package     WooCommerce\POS\Emails
	 * @extends     WC_Email_POS_Base
	 */
	class WC_Email_Customer_POS_Completed_Order extends WC_Email_POS_Base {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'pos_customer_completed_order';
			$this->customer_email = true;
			$this->title          = __( 'POS Completed order', 'woocommerce-pos' );
			$this->description    = __( 'Order complete emails can be sent to customers when their orders are marked completed in POS.', 'woocommerce-pos' );
			$this->template_html  = 'emails/customer-pos-completed-order.php';
			$this->template_plain = 'emails/plain/customer-pos-completed-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			$refund_page_id = get_option( 'woocommerce_refund_returns_page_id' );
			$refund_page    = $refund_page_id ? get_post( $refund_page_id ) : null;

			if ( $refund_page && 'publish' === $refund_page->post_status ) {
				$refund_page_url = get_permalink( $refund_page_id );
				if ( $refund_page_url ) {
					$this->placeholders['{refund_returns_policy_url}'] = $refund_page_url;
				}
			}

			// Hook into the REST API action to send this email when requested.
			add_action( 'woocommerce_rest_order_actions_email_send', array( $this, 'maybe_trigger_from_api' ), 10, 2 );

			// Add this email template to the list of valid templates for orders
			add_filter( 'woocommerce_rest_order_actions_email_valid_template_classes', array( $this, 'add_to_valid_template_classes' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Send an email to customers when their POS order is completed.', 'woocommerce-pos' )
				: __( 'POS order completed emails are sent to customers when their in-store orders are marked as completed.', 'woocommerce-pos' );

			$this->manual = true;
		}

		/**
		 * Trigger the sending of this email when requested via the REST API.
		 *
		 * @param int    $order_id    The order ID.
		 * @param string $template_id The email template ID.
		 */
		public function maybe_trigger_from_api( $order_id, $template_id ) {
			if ( $this->id === $template_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$this->trigger( $order_id, $order );
				}
			}
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_subject( $paid = false ) {
			return __( 'Your in-store purchase #{order_number} on {site_title}', 'woocommerce-pos' );
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $paid Whether the order has been paid or not.
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_heading( $paid = false ) {
			return __( 'Thank you for your in-store purchase', 'woocommerce-pos' );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->object->has_status( array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ) ) {
				$subject = $this->get_option( 'subject_paid', $this->get_default_subject( true ) );

				return apply_filters( 'woocommerce_email_subject_customer_pos_completed_order_paid', $this->format_string( $subject ), $this->object, $this );
			}

			$subject = $this->get_option( 'subject', $this->get_default_subject() );
			return apply_filters( 'woocommerce_email_subject_customer_pos_completed_order', $this->format_string( $subject ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->object->has_status( wc_get_is_paid_statuses() ) ) {
				$heading = $this->get_option( 'heading_paid', $this->get_default_heading( true ) );
				return apply_filters( 'woocommerce_email_heading_customer_pos_completed_order_paid', $this->format_string( $heading ), $this->object, $this );
			}

			$heading = $this->get_option( 'heading', $this->get_default_heading() );
			return apply_filters( 'woocommerce_email_heading_customer_pos_completed_order', $this->format_string( $heading ), $this->object, $this );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks for shopping with us in-store! If you need any help with your purchase, please contact us at {store_email}.', 'woocommerce-pos' )
				: __( 'Thanks for shopping with us in-store!', 'woocommerce-pos' );
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-pos' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'subject'               => array(
					'title'       => __( 'Subject', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'               => array(
					'title'       => __( 'Email heading', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'subject_paid'          => array(
					'title'       => __( 'Subject (paid)', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_paid'          => array(
					'title'       => __( 'Email heading (paid)', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading( true ),
					'default'     => '',
				),
				'additional_content'    => array(
					'title'       => __( 'Additional content', 'woocommerce-pos' ),
					'description' => __( 'Text to appear below the main email content.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => '',
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),				
				'email_type'            => array(
					'title'       => __( 'Email type', 'woocommerce-pos' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'woocommerce-pos' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
			if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
				$this->form_fields['cc']  = $this->get_cc_field();
				$this->form_fields['bcc'] = $this->get_bcc_field();
			}
		}

		/**
		 * Add this email template to the list of valid templates for orders.
		 *
		 * @param array    $valid_template_classes Array of valid template class names.
		 * @param WC_Order $order                  The order.
		 * @return array Modified array of valid template class names.
		 */
		public function add_to_valid_template_classes( $valid_template_classes, $order ) {
			// Can add conditions here if needed, e.g., only for certain order statuses.
			$valid_template_classes[] = get_class( $this );
			return $valid_template_classes;
		}
	}

endif;

return new WC_Email_Customer_POS_Completed_Order();
