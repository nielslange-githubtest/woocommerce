<?php
/**
 * WC_BIS_Email_Preview class.
 *
 * @package  WooCommerce Back In Stock Notifications
 * @since    x.x.x
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BIS email preview class
 *
 * @interface WC_BIS_Email_Previewable
 * @version  x.x.x
 */
class WC_BIS_Email_Preview {
	/**
	 * The email being previewed
	 *
	 * @var string
	 */
	private $email_type;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );
	}

	/**
	 * Get a dummy product.
	 *
	 * @return WC_Product
	 */
	private function get_dummy_product(): WC_Product {
		$product = new WC_Product();
		$product->set_name( 'Dummy Product' );
		$product->set_price( 25 );

		/**
		 * Filter the dummy back in stock notification product object used in email previews.
		 *
		 * @since x.x.x
		 * @param WC_Product $product The dummy product object.
		 * @param string     $email_type The email type being previewed.
		 */
		$product = apply_filters( 'woocommerce_bis_email_preview_dummy_product', $product, $this->email_type );

		if ( ! $product instanceof WC_Product ) {
			_doing_it_wrong( __METHOD__, 'The return value of woocommerce_bis_email_preview_dummy_product must be an instance of WC_Product.', 'x.x.x' );
		}

		return $product;
	}

	/**
	 * Get a dummy notification object.
	 *
	 * @return WC_BIS_Notification_Data
	 */
	private function get_dummy_notification(): WC_BIS_Notification_Data {
		$product      = $this->get_dummy_product();
		$notification = new WC_BIS_Notification_Data(
			array(
				'product' => $product,
			)
		);

		// Add a "fake" hash key and IV to prevent warnings when rendering the preview.
		$notification->update_meta( '_hash_key', '16b69799f092b11b' );
		$notification->update_meta( '_hash_iv', '16b69799f092b11b' );

		/**
		 * Filter the dummy back in stock notification object used in email previews.
		 *
		 * @since x.x.x
		 * @param WC_BIS_Notification_Data $notification The dummy product object.
		 * @param string     $email_type The email type being previewed.
		 */
		$notification = apply_filters( 'woocommerce_bis_email_preview_dummy_notification', $notification, $this->email_type );

		if ( ! $notification instanceof WC_BIS_Notification_Data ) {
			_doing_it_wrong( __METHOD__, 'The return value of woocommerce_bis_email_preview_dummy_notification must be an instance of WC_BIS_Notification_Data.', 'x.x.x' );
		}

		return $notification;
	}

	/**
	 * Prepares the email for preview.
	 *
	 * @template T of WC_Email
	 * @param T $email The email object being previewed.
	 * @return T
	 */
	public function prepare_email_for_preview( $email ) {
		$this->email_type = get_class( $email );

		if ( ! $this->is_bis_email() || ! $email instanceof WC_BIS_Email_Previewable ) {
			return $email;
		}

		$notification = $this->get_dummy_notification( $email );

		$email->prepare_email( $notification );
		return $email;
	}

	/**
	 * Check if the email being previewed is a back in stock email.
	 *
	 * @return bool
	 */
	private function is_bis_email(): bool {
		return isset( WC_BIS_Emails::$email_classes[ $this->email_type ] );
	}
}

new WC_BIS_Email_Preview();
