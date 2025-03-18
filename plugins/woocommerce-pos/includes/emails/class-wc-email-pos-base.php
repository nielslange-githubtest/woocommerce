<?php
/**
 * Class WC_Email_POS_Base file.
 *
 * @package WooCommerce\POS\Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_POS_Base', false ) ) :

	/**
	 * Base class for all POS emails.
	 *
	 * This abstract class provides common functionality for all POS email types.
	 *
	 * @class       WC_Email_POS_Base
	 * @version     1.0.0
	 * @package     WooCommerce\POS\Emails
	 * @extends     WC_Email
	 */
	abstract class WC_Email_POS_Base extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Set template base to POS plugin directory
			$this->template_base = WC_POS_PLUGIN_DIR . 'templates/';
			
			// Call parent constructor
			parent::__construct();
			
			// Default to manual sending
			$this->manual = true;
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

		/**
		 * Get the refund and returns policy text.
		 *
		 * @return string
		 */
		public function get_refund_returns_policy() {
			$policy_text = $this->get_option( 'refund_returns_policy', '' );
			return $this->format_string( $policy_text );
		}
		
		/**
		 * Show the order details table with payment auth code.
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
					)
				);
			}
		}

		public function order_item_quantity( $quantity_display, $item ) {
			$order = isset($this->object) ? $this->object : null;
			$unit_price = '';
			
			if ($order && is_a($item, 'WC_Order_Item_Product')) {
				$unit_price = $order->get_formatted_item_subtotal($item) . ' ';

				$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
				if (!$email_improvements_enabled) {
					$unit_price = $unit_price . '&times;';
				}
			}

			return $unit_price . $quantity_display;
		}

		public function order_item_totals($total_rows, $order, $tax_display ) {
			$auth_code = $order->get_meta( '_charge_id', true );
			if ( $auth_code ) {
				$total_rows['payment_auth_code'] = array(
					'type'  => 'payment_auth_code',
					'label' => __( 'Auth code:', 'woocommerce' ),
					'value' => $auth_code,
				);
			}
			return $total_rows;
		}

		/**
		 * Get content html with payment auth code included.
		 *
		 * @return string
		 */
		public function get_content_html() {
			// Add filter to include unit price in the quantity column for order items table.
			add_filter( 'woocommerce_email_order_item_quantity', array( $this, 'order_item_quantity' ), 10, 2 );

			// Add filter to include payment auth code in the order item totals table.
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10, 3 );

			// TODO: date_paid, if needed.
			// Custom action to show the order details.
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
			remove_action( 'woocommerce_pos_email_order_details', array( $this, 'order_details' ), 10 );
			remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10 );
			remove_filter( 'woocommerce_email_order_item_quantity', array( $this, 'order_item_quantity' ), 10 );
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
	}

endif; 