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

		}

		/**
		 * Get the store email text.
		 *
		 * @return string
		 */
		protected function get_pos_store_email() {
			$email_text = $this->get_option( 'pos_store_email', '' );
			return $this->format_string( $email_text );
		}

		/**
		 * Get the store email text.
		 *
		 * @return string
		 */
		protected function get_default_pos_store_email() {
			return $this->get_store_email();
		}

		/**
		 * Get the store email text.
		 *
		 * @return string
		 */
		protected function get_pos_store_phone_number() {
			$phone_number_text = $this->get_option( 'pos_store_phone_number', '' );
			return $this->format_string( $phone_number_text );
		}

		/**
		 * Get the store address text.
		 *
		 * @return string
		 */
		protected function get_pos_store_address() {
			$address_text = $this->get_option( 'pos_store_address', '' );
			return $this->format_string( $address_text );
		}

		/**
		 * Get the store address text.
		 *
		 * @return string
		 */
		protected function get_default_pos_store_address() {
			return $this->get_store_address();
		}
		
		/**
		 * Placeholder of the refund & returns policy content.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		protected function get_refund_returns_policy_placeholder() {
			return __( 'Brief statement about the refund & returns policy', 'woocommerce-pos' );
		}

		/**
		 * Get the refund and returns policy text.
		 *
		 * @return string
		 */
		protected function get_refund_returns_policy() {
			$policy_text = $this->get_option( 'refund_returns_policy', '' );
			return $this->format_string( $policy_text );
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
			$cash_payment_change_due_amount = $order->get_meta( '_cash_change_amount', true );
			if ( $cash_payment_change_due_amount !== null && $cash_payment_change_due_amount !== '' ) {
				$total_rows['cash_payment_change_due_amount'] = array(
					'type'  => 'cash_payment_change_due_amount',
					'label' => __( 'Change due:', 'woocommerce' ),
					'value' => $cash_payment_change_due_amount,
				);
			}

			$auth_code = $order->get_meta( '_charge_id', true );
			if ( $auth_code !== null && $auth_code !== '' ) {
				$total_rows['payment_auth_code'] = array(
					'type'  => 'payment_auth_code',
					'label' => __( 'Auth code:', 'woocommerce' ),
					'value' => $auth_code,
				);
			}

			if ( $order->get_date_paid() !== null ) {
				$total_rows['date_paid'] = array(
					'type'  => 'date_paid',
					'label' => __( 'Time of payment:', 'woocommerce' ),
					'value' => wc_format_datetime( $order->get_date_paid(), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
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
			$this->add_filters_before_content();

			$content = wc_get_template_html(
				$this->template_html,
				array(
					'order'                 => $this->object,
					'email_heading'         => $this->get_heading(),
					'additional_content'    => $this->get_additional_content(),
					'pos_store_email'       => $this->get_pos_store_email(),
					'pos_store_phone_number' => $this->get_pos_store_phone_number(),
					'pos_store_address'     => $this->get_pos_store_address(),
					'refund_returns_policy' => $this->get_refund_returns_policy(),
					'sent_to_admin'         => false,
					'plain_text'            => false,
					'email'                 => $this,
				)
			);

			$this->remove_filters_after_content();
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

		protected function add_filters_before_content() {
			// Add filter to include unit price in the quantity column for order items table.
			add_filter( 'woocommerce_email_order_item_quantity', array( $this, 'order_item_quantity' ), 10, 2 );
			// Add filter to include payment auth code in the order item totals table.
			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10, 3 );
		}

		protected function remove_filters_after_content() {
			// Remove action and filter after generating content to avoid affecting other emails.
			remove_filter( 'woocommerce_get_order_item_totals', array( $this, 'order_item_totals' ), 10 );
			remove_filter( 'woocommerce_email_order_item_quantity', array( $this, 'order_item_quantity' ), 10 );
		}

		/**
		 * Get the store email address.
		 *
		 * @return string
		 */
		protected function get_store_email() {
			return get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) );
		}

		/**
		 * Get store address formatted for emails.
		 *
		 * @return string
		 */
		protected function get_store_address() {
			add_filter(
				'woocommerce_formatted_address_force_country_display',
				array( $this, 'get_store_address_force_country_display' ),
				5
			);
			$result = wp_specialchars_decode(
				WC()->countries->get_formatted_address(
					array(
						'address_1' => WC()->countries->get_base_address(),
						'address_2' => WC()->countries->get_base_address_2(),
						'city'      => WC()->countries->get_base_city(),
						'state'     => WC()->countries->get_base_state(),
						'country'   => WC()->countries->get_base_country(),
						'postcode'  => WC()->countries->get_base_postcode(),
					),
					'<br/>'
				)
			);
			remove_filter(
				'woocommerce_formatted_address_force_country_display',
				array( $this, 'get_store_address_force_country_display' )
			);
			return $result;
		}

		/**
		 * Force country display, used by WC_Emails::get_store address() method
		 *
		 * @return bool
		 */
		public function get_store_address_force_country_display() {
			return true;
		}
	}

endif; 