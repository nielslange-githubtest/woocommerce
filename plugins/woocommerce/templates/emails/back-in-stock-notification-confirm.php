<?php
/**
 * Sign-up confirmation email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/back-in-stock-notification-confirm.php.
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

declare( strict_types = 1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook: woocommerce_email_header.
 *
 * @since 9.9.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 *
 * @param string $email_heading The email heading.
 * @param WC_Email $email The email object.
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

/**
 * Hook: woocommerce_email_confirm_notification_html.
 *
 * @since 9.9.0
 *
 * @hooked WC_BIS_Emails::confirm_notification_email_html() Output the notification content
 *
 * @param WC_BIS_Notification_Data $notification The notification data.
 * @param WC_Email $email The email object.
 */
do_action( 'woocommerce_email_confirm_notification_html', $notification, $email );

/**
 * Hook: woocommerce_email_footer.
 *
 * @since 9.9.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 *
 * @param WC_Email $email The email object.
 */
do_action( 'woocommerce_email_footer', $email );
