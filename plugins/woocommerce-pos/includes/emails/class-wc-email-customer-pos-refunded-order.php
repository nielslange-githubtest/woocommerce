<?php
/**
 * Class WC_Email_Customer_POS_Refunded_Order file.
 *
 * @package WooCommerce\POS\Emails
 */

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
if ( class_exists( 'WC_Email_POS_Base', false ) && ! class_exists( 'WC_Email_Customer_POS_Refunded_Order', false ) ) :

	/**
	 * POS Order refunded email.
	 *
	 * An email sent to the customer when a POS order is refunded.
	 *
	 * @class       WC_Email_Customer_POS_Refunded_Order
	 * @version     1.0.0
	 * @package     WooCommerce\POS\Emails
	 * @extends     WC_Email_POS_Base
	 */
	class WC_Email_Customer_POS_Refunded_Order extends WC_Email_POS_Base {

		/**
		 * Refund order.
		 *
		 * @var WC_Order|bool
		 */
		public $refund;

		/**
		 * Is the order partial refunded?
		 *
		 * @var bool
		 */
		public $partial_refund;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'pos_customer_refunded_order';
			$this->customer_email = true;
			$this->title          = __( 'POS Refunded order', 'woocommerce-pos' );
			$this->description    = __( 'Refund emails can be sent to customers when their orders are refunded in POS.', 'woocommerce-pos' );
			$this->template_html  = 'emails/customer-pos-refunded-order.php';
			$this->template_plain = 'emails/plain/customer-pos-refunded-order.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Hook into the REST API action to send this email when requested.
			add_action( 'woocommerce_rest_order_actions_email_send', array( $this, 'maybe_trigger_from_api' ), 10, 2 );

			// Add this email template to the list of valid templates for orders
			add_filter( 'woocommerce_rest_order_actions_email_valid_template_classes', array( $this, 'add_to_valid_template_classes' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();

			// Must be after parent's constructor which sets `email_improvements_enabled` property.
			$this->description = $this->email_improvements_enabled
				? __( 'Send an email to customers when their POS order is refunded.', 'woocommerce-pos' )
				: __( 'POS order refunded emails are sent to customers when their in-store orders are refunded.', 'woocommerce-pos' );
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
					$this->trigger( $order_id, false, $order );
				}
			}
		}

		/**
		 * Full refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_full( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, false, null, $refund_id );
		}

		/**
		 * Partial refund notification.
		 *
		 * @param int $order_id Order ID.
		 * @param int $refund_id Refund ID.
		 */
		public function trigger_partial( $order_id, $refund_id = null ) {
			$this->trigger( $order_id, true, null, $refund_id );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param bool           $partial_refund Whether it is a partial refund or a full refund.
		 * @param WC_Order|false $order Order object.
		 * @param int            $refund_id Refund ID.
		 */
		public function trigger( $order_id, $partial_refund = false, $order = false, $refund_id = null ) {
			$this->setup_locale();
			$this->partial_refund = $partial_refund;
			$this->id             = $this->partial_refund ? 'pos_customer_partially_refunded_order' : 'pos_customer_refunded_order';

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( ! empty( $refund_id ) ) {
				$this->refund = wc_get_order( $refund_id );
			} else {
				$this->refund = false;
			}

			if ( $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_subject( $partial = false ) {
			if ( $partial ) {
				return __( 'Your in-store purchase #{order_number} on {site_title} has been partially refunded', 'woocommerce-pos' );
			} else {
				return __( 'Your in-store purchase #{order_number} on {site_title} has been refunded', 'woocommerce-pos' );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return $this->email_improvements_enabled
					? __( 'Partial refund: Order {order_number}', 'woocommerce-pos' )
					: __( 'Partial Refund: Order {order_number}', 'woocommerce-pos' );
			} else {
				return $this->email_improvements_enabled
					? __( 'Order refunded: {order_number}', 'woocommerce-pos' )
					: __( 'Order Refunded: {order_number}', 'woocommerce-pos' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			if ( $this->partial_refund ) {
				$subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
				return apply_filters( 'woocommerce_email_subject_customer_pos_partially_refunded_order', $this->format_string( $subject ), $this->object, $this );
			} else {
				$subject = $this->get_option( 'subject_full', $this->get_default_subject() );
				return apply_filters( 'woocommerce_email_subject_customer_pos_refunded_order', $this->format_string( $subject ), $this->object, $this );
			}
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			if ( $this->partial_refund ) {
				$heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
				return apply_filters( 'woocommerce_email_heading_customer_pos_partially_refunded_order', $this->format_string( $heading ), $this->object, $this );
			} else {
				$heading = $this->get_option( 'heading_full', $this->get_default_heading() );
				return apply_filters( 'woocommerce_email_heading_customer_pos_refunded_order', $this->format_string( $heading ), $this->object, $this );
			}
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'If you need any help with your refund, please contact us at {store_email}.', 'woocommerce-pos' )
				: __( 'We hope to see you again soon.', 'woocommerce-pos' );
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			/* translators: %s: list of placeholders */
			$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-pos' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
			$this->form_fields = array(
				'subject_full'          => array(
					'title'       => __( 'Subject (full refund)', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading_full'          => array(
					'title'       => __( 'Email heading (full refund)', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'subject_partial'       => array(
					'title'       => __( 'Subject (partial refund)', 'woocommerce-pos' ),
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => $placeholder_text,
					'placeholder' => $this->get_default_subject( true ),
					'default'     => '',
				),
				'heading_partial'       => array(
					'title'       => __( 'Email heading (partial refund)', 'woocommerce-pos' ),
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
				'pos_store_email'   => array(
					'title'       => __( 'Email address', 'woocommerce-pos' ),
					'description' => __( 'Email address to appear in the contact details section below the main email content.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'placeholder' => '',
					'type'        => 'text',
					'default'     => $this->get_pos_store_email(),
					'desc_tip'    => true,
				),
				'pos_store_phone_number'   => array(
					'title'       => __( 'Phone number', 'woocommerce-pos' ),
					'description' => __( 'Phone number to appear in the contact details section below the main email content.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'placeholder' => '',
					'type'        => 'text',
					'default'     => $this->get_pos_store_phone_number(),
					'desc_tip'    => true,
				),
				'pos_store_address'   => array(
					'title'       => __( 'Store address', 'woocommerce-pos' ),
					'description' => __( 'Address of the store to appear in the contact details section below the main email content.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => '',
					'type'        => 'textarea',
					'default'     => $this->get_pos_store_address(),
					'desc_tip'    => true,
				),
				'refund_returns_policy' => array(
					'title'       => __( 'Refund & returns policy', 'woocommerce-pos' ),
					'description' => __( 'Text to appear below the main email and additional content about the refund & returns policy.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => $this->get_refund_returns_policy_placeholder(),
					'type'        => 'textarea',
					'default'     => $this->get_refund_returns_policy(),
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

		/**
		 * Check if this order was created from a POS (Point of Sale) system.
		 *
		 * @return bool True if this is a POS order, false otherwise.
		 */
		private function is_pos_order($order): bool {
			return 'pos' === $order->get_meta( '_wc_order_attribution_source_type' );
		}
	}

endif;

return new WC_Email_Customer_POS_Refunded_Order();
