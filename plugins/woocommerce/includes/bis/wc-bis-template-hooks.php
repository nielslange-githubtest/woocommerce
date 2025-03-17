<?php
/**
 * Template Hooks
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notication email template parts.
 */
add_action( 'woocommerce_bis_email_notification_product_before_title', 'wc_bis_email_notification_product_image', 10, 2 );
add_action( 'woocommerce_bis_email_notification_product_title', 'wc_bis_email_notification_product_title', 10, 2 );
add_action( 'woocommerce_bis_email_notification_product_after_title', 'wc_bis_email_notification_product_attributes', 10, 2 );
add_action( 'woocommerce_bis_email_notification_product_after_title', 'wc_bis_email_notification_product_price', 20, 2 );
