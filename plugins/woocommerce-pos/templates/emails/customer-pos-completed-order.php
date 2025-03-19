<?php
/**
 * Customer POS completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-pos-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\POS\Templates\Emails
 * @version 1.0.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Executes the e-mail header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce-pos' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce-pos' ) );
}
?>
</p>
<?php if ( $order->has_status( array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ) ) { ?>
	<p>
	<?php
	printf(
		/* translators: %s: Site title */
		esc_html__( 'Your in-store purchase from %s has been completed.', 'woocommerce-pos' ),
		wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
	);
	?>
	</p>
<?php } else { ?>
	<p>
	<?php
	printf(
		/* translators: %s: Site title */
		esc_html__( 'Thank you for your in-store purchase from %s.', 'woocommerce-pos' ),
		wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
	);
	?>
	</p>
<?php } ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Email_Customer_POS_Completed_Order::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * Show store address - this is set in each email's settings.
 */
if ( ! empty( $pos_store_address ) ) {
	echo '<div class="pos-store-address">';
	echo '<h2>' . esc_html__( 'Store Address', 'woocommerce-pos' ) . '</h2>';
	echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) );
	echo '</div>';
}

/**
 * Show refund & returns policy - this is set in each email's settings.
 */
if ( ! empty( $refund_returns_policy ) ) {
	echo '<div class="refund-returns-policy">';
	echo '<h2>' . esc_html__( 'Refund & Returns Policy', 'woocommerce-pos' ) . '</h2>';
	echo wp_kses_post( wpautop( wptexturize( $refund_returns_policy ) ) );
	echo '</div>';
}

/**
 * Executes the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email ); 