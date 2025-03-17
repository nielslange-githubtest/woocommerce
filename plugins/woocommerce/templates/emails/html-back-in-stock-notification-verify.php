<?php
/**
 * Sign-up verification email content.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/html-back-in-stock-notification-verify.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce Back In Stock Notifications
 * @version 9.9.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table border="0" cellpadding="0" cellspacing="0" id="notification__container"><tr><td>

	<div id="notification__into_content">
		<?php echo wp_kses_post( wpautop( wptexturize( $intro_content ) ) ); ?>
	</div>

	<div id="notification__product">

		<?php
		/**
		 * Hook: woocommerce_email_notification_product_before_title.
		 *
		 * @since 9.9.0
		 *
		 * @hooked woocommerce_email_notification_product_image - 10
		 */
		do_action( 'woocommerce_bis_email_notification_product_before_title', $product, $notification );

		/**
		 * Hook: woocommerce_email_notification_product_title.
		 *
		 * @since 9.9.0
		 *
		 * @hooked woocommerce_email_notification_product_title - 10
		 */
		do_action( 'woocommerce_bis_email_notification_product_title', $product, $notification );

		/**
		 * Hook: woocommerce_email_notification_product_after_title.
		 *
		 * @since 9.9.0
		 *
		 * @hooked woocommerce_email_notification_product_attributes - 10
		 * @hooked woocommerce_email_notification_product_price - 20
		 */
		do_action( 'woocommerce_bis_email_notification_product_after_title', $product, $notification );
		?>

		/**
		 * Hook: woocommerce_bis_email_verify_button_text.
		 *
		 * @since 9.9.0
		 *
		 * @param string $button_text The button text.
		 * @param WC_BIS_Notification_Data $notification The notification data.
		 * @return string
		 */
		<a href="<?php echo esc_url( $verification_href ); ?>" id="notification__action_button"><?php echo esc_html( apply_filters( 'woocommerce_bis_email_verify_button_text', _x( 'Confirm', 'Verify email notification', 'woocommerce' ), $notification ) ); ?></a>

		<div id="notification__verification_expiration">
			<?php
			// translators: %$s placeholder is the verification expiration threshold.
			echo wp_kses_post( sprintf( esc_html__( 'This link will remain active for %s.', 'woocommerce' ), $verification_expiration_threshold ) );
			?>
		</div>

	</div>

	<table id="notification__footer"><tr><td>
		<?php echo esc_html( __( 'You have received this message because your e-mail address was used to sign up for stock notifications on our store. Wasn\'t you? Please get in touch with us if you keep receiving these messages.', 'woocommerce' ) ); ?>
		<br><br>
		<?php

		/**
		 * Show user-defined additional content - this is set in each email's settings.
		 */
		if ( $additional_content ) {
			echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
		}
		?>
	</td></tr></table>

</td></tr></table>
