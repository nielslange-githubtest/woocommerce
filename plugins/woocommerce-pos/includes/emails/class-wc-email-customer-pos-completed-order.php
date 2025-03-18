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

if ( ! class_exists( 'WC_Email_Customer_POS_Completed_Order', false ) ) :

	/**
	 * POS Order completed email.
	 *
	 * An email sent to the customer when a POS order is completed.
	 *
	 * @class       WC_Email_Customer_POS_Completed_Order
	 * @version     1.0.0
	 * @package     WooCommerce\POS\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_POS_Completed_Order extends WC_Email {

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
		 * Add unit price in quantity column in order items.
		 *
		 * @param array $args Order items arguments.
		 * @return array Modified arguments.
		 */
		public function add_unit_price_in_quantity_arg( $args ) {
			$args['includes_unit_price_with_quantity'] = true;
			return $args;
		}

		/**
		 * Show the order details table
		 *
		 * @param WC_Order $order         Order instance.
		 * @param bool     $sent_to_admin If should sent to admin.
		 * @param bool     $plain_text    If is plain text email.
		 * @param string   $email         Email address.
		 */
		public function order_details( $order, $sent_to_admin = false, $plain_text = false, $email = '' ) {
			if ( $plain_text ) {
				wc_get_template(
					'emails/plain/email-order-details.php',
					array(
						'order'                      => $order,
						'sent_to_admin'              => $sent_to_admin,
						'plain_text'                 => $plain_text,
						'email'                      => $email,
						'includes_payment_auth_code' => true,
					)
				);
			} else {
				wc_get_template(
					'emails/email-order-details.php',
					array(
						'order'                      => $order,
						'sent_to_admin'              => $sent_to_admin,
						'plain_text'                 => $plain_text,
						'email'                      => $email,
						'includes_payment_auth_code' => true,
					)
				);
			}
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
		 * Placeholder of the refund & returns policy content.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function get_refund_returns_policy_placeholder() {
			return __( 'Brief statement about the refund & returns policy', 'woocommerce-pos' );
		}

		public function get_refund_returns_policy() {
			$policy_text = $this->get_option( 'refund_returns_policy', '' );
			return $this->format_string( $policy_text );
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
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			// Add filter to include unit price in the quantity column for order items table.
			add_filter( 'woocommerce_email_order_items_args', array( $this, 'add_unit_price_in_quantity_arg' ), 10, 1 );
			// Custom action to show the order details table with payment auth code.
			add_action( 'woocommerce_pos_email_order_details', array( $this, 'order_details' ), 10, 4 );
			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'                 => $this->object,
					'email_heading'         => $this->get_heading(),
					'additional_content'    => $this->get_additional_content(),
					'refund_returns_policy' => $this->get_refund_returns_policy(),
					'sent_to_admin'         => false,
					'plain_text'            => false,
					'email'                 => $this,
				)
			);

			// Remove action and filter after generating content to avoid affecting other emails.
			remove_action( 'woocommerce_email_order_details', array( $this, 'order_details' ), 10 );
			remove_filter( 'woocommerce_email_order_items_args', array( $this, 'add_unit_price_in_quantity_arg' ), 10 );

			return $content;
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
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
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
					'placeholder' => __( 'N/A', 'woocommerce-pos' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
					'desc_tip'    => true,
				),
				'refund_returns_policy' => array(
					'title'       => __( 'Refund & returns policy', 'woocommerce-pos' ),
					'description' => __( 'Text to appear below the main email and additional content about the refund & returns policy.', 'woocommerce-pos' ) . ' ' . $placeholder_text,
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => $this->get_refund_returns_policy_placeholder(),
					'type'        => 'textarea',
					'default'     => '',
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