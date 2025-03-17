<?php
/**
 * Back in Stock Notification Received template.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/back-in-stock-notification-received.php.
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


echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook: woocommerce_email_intro_content.
 *
 * @since 9.9.0
 *
 * @param string $intro_content The intro content.
 * @param WC_BIS_Notification_Data $notification The notification data.
 */
echo esc_html( wp_strip_all_tags( wptexturize( apply_filters( 'woocommerce_email_intro_content', $intro_content, $notification ) ) ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Hook: woocommerce_email_footer_text.
 *
 * @since 9.9.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
