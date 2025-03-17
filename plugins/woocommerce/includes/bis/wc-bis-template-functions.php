<?php
/**
 * Template Functions
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

if ( ! function_exists( 'wc_bis_email_notification_product_image' ) ) {
	/**
	 * Display product image.
	 *
	 * @since 1.2.0
	 *
	 * @param  object $product     The product.
	 * @param  object $notification The notification.
	 */
	function wc_bis_email_notification_product_image( $product, $notification ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$image     = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );
		$image_src = is_array( $image ) && isset( $image[0] ) ? $image[0] : '';

		ob_start();
		if ( $image_src ) { ?>
				<div id="notification__product__image">
					<img src="<?php echo esc_attr( $image_src ); ?>" alt="<?php echo esc_attr( $product->get_title() ); ?>" width="220"/>
				</div>
			<?php
		}
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'wc_bis_email_notification_product_title' ) ) {
	/**
	 * Display product title.
	 *
	 * @since 1.2.0
	 *
	 * @param  object $product     The product.
	 * @param  object $notification The notification.
	 */
	function wc_bis_email_notification_product_title( $product, $notification ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		ob_start();
		?>
		<div id="notification__product__title"><?php echo esc_html( $product->get_name() ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'wc_bis_email_notification_product_attributes' ) ) {
	/**
	 * Display product attributes.
	 *
	 * @since 1.2.0
	 *
	 * @param  object $product     The product.
	 * @param  object $notification The notification.
	 */
	function wc_bis_email_notification_product_attributes( $product, $notification ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		ob_start();
		?>
		<div id="notification__product__attributes"><?php echo wp_kses_post( $notification->get_product_formatted_variation_list( false, 'email' ) ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'wc_bis_email_notification_product_price' ) ) {
	/**
	 * Display product price.
	 *
	 * @since 1.2.0
	 *
	 * @param  object $product     The product.
	 * @param  object $notification The notification.
	 */
	function wc_bis_email_notification_product_price( $product, $notification ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		ob_start();
		?>
		<div id="notification__product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'wc_bis_get_activity_description' ) ) {

	/**
	 * Get activity description.
	 *
	 * @since 1.2.0
	 *
	 * @param  object $activity_data The activity data.
	 * @return string
	 */
	function wc_bis_get_activity_description( $activity_data ) {
		$notification = wc_bis_get_notification( $activity_data->get_notification_id() );
		if ( ! is_a( $notification, 'WC_BIS_Notification_Data' ) ) {
			return;
		}

		$description = '';
		$product     = $notification->get_product();

		switch ( $activity_data->get_type() ) {
			case 'created':
				if ( $notification && $product ) {
					/* translators: %1$s: notification id, %2$s: product name */
					$description = sprintf( __( 'Created "%2$s" notification (#%1$d)', 'woocommerce' ), $notification->get_id(), $notification->get_product_name() );
				} else {
					/* translators: %1$s: notification id*/
					$description = sprintf( __( 'Created #%1$s notification', 'woocommerce' ), $activity_data->get_notification_id() );
				}

				break;
			case 'delivered':
				if ( $notification && $product ) {
					/* translators: %1$s: notification id, %2$s: product name */
					$description = sprintf( __( 'Delivered "%2$s" notification (#%1$d)', 'woocommerce' ), $notification->get_id(), $notification->get_product_name() );
				} else {
					/* translators: %d: notification id*/
					$description = sprintf( __( 'Delivered notification (#%d)', 'woocommerce' ), $activity_data->get_notification_id() );
				}
				break;
			case 'reactivated':
				if ( $notification && $product ) {
					/* translators: %1$s: notification id, %2$s: product name */
					$description = sprintf( __( 'Reactivated "%2$s" notification (#%1$d)', 'woocommerce' ), $notification->get_id(), $notification->get_product_name() );
				} else {
					/* translators: %d: notification id*/
					$description = sprintf( __( 'Reactivated notification (#%d)', 'woocommerce' ), $activity_data->get_notification_id() );
				}
				break;
			case 'deactivated':
				if ( $notification && $product ) {
					/* translators: %1$s: notification id, %2$s: product name */
					$description = sprintf( __( 'Deactivated "%2$s" notification (#%1$d)', 'woocommerce' ), $notification->get_id(), $notification->get_product_name() );
				} else {
					/* translators: %d: notification id*/
					$description = sprintf( __( 'Deactivated notification (#%d)', 'woocommerce' ), $activity_data->get_notification_id() );
				}
				break;
		}

		return $description;
	}
}

if ( ! function_exists( 'wc_bis_get_form_header_default_text' ) ) {

	/**
	 * Default form header text.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_header_default_text() {
		return __( 'Want to be notified when this product is back in stock?', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_header_signed_up_default_text' ) ) {

	/**
	 * Default form header text when user already subscribed to the waitlist.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_header_signed_up_default_text() {
		return __( 'You have already joined the waitlist! Click {manage_account_link} to manage your notifications.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_button_default_text' ) ) {

	/**
	 * Default form button text.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_button_default_text() {
		return __( 'Notify me', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_header_signed_up_link_default_text' ) ) {

	/**
	 * Default form header link text when user already signed up to the waitlist.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_header_signed_up_link_default_text() {
		return __( 'here', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_privacy_default_text' ) ) {

	/**
	 * Default form privacy text.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_privacy_default_text() {
		return __( 'Use this e-mail address to send me availability alerts and updates.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_signups_count_plural_default_text' ) ) {

	/**
	 * Default form count sign-ups text for plural customers.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_signups_count_plural_default_text() {
		return __( '{customers_count} customers have joined the waitlist.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_form_signups_count_default_text' ) ) {

	/**
	 * Default form count sign-ups text for single customer.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_form_signups_count_default_text() {
		return __( '1 customer has joined the waitlist.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_loop_signup_prompt_default_text' ) ) {

	/**
	 * Default signup prompt text.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_loop_signup_prompt_default_text() {
		return __( 'Out of stock. {prompt_link} to be notified when this product becomes available.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_loop_signup_prompt_link_default_text' ) ) {

	/**
	 * Default signup prompt link text.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_loop_signup_prompt_link_default_text() {
		return __( 'Join the waitlist', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_loop_signup_prompt_subscribed_default_text' ) ) {

	/**
	 * Default text for signup prompt when user already signed up.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_loop_signup_prompt_signed_up_default_text() {
		return __( 'Out of stock. {prompt_link} and will be notified when this product becomes available.', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_get_loop_signup_prompt_subscribed_link_default_text' ) ) {

	/**
	 * Default link text for signup prompt when user already signed up.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wc_bis_get_loop_signup_prompt_signed_up_link_default_text() {
		return __( 'You have joined the waitlist', 'woocommerce' );
	}
}

if ( ! function_exists( 'wc_bis_fetch_and_build_string' ) ) {

	/**
	 * Helper function.
	 *
	 * Build a string with option values and default values. If placeholder is set, this function will replace it depending on the placeholder_data argument. This function only works for one placeholder.
	 *
	 * @since 1.2.0
	 *
	 * @param  string $key The key.
	 * @param  string $placeholder (Optional) The placeholder.
	 * @param  mixed  $placeholder_data (Optional) The placeholder data.
	 * @return string
	 */
	function wc_bis_build_shop_text( $key, $placeholder = '', $placeholder_data = false ) {

		// Parse key.
		$key = (string) sanitize_key( $key );
		if ( empty( $key ) ) {
			return '';
		}

		// Calculate db option and default function name.
		$option_name   = sprintf( 'wc_bis_%s_text', $key );
		$function_name = sprintf( 'wc_bis_get_%s_default_text', $key );

		// Consistency mapper.
		switch ( $key ) {
			case 'form_privacy':
				$option_name = 'wc_bis_create_new_account_optin_text';
				break;
			case 'form_signups_count_plural':
				$option_name = 'wc_bis_product_registrations_plural_text';
				break;
			case 'form_signups_count':
				$option_name = 'wc_bis_product_registrations_text';
				break;
		}

		// Build main text.
		$text = get_option( $option_name );
		if ( empty( $text ) && function_exists( $function_name ) ) {
			$text = $function_name();
		}

		if ( empty( $text ) ) {
			return '';
		}

		if ( empty( $placeholder ) ) {
			return $text;
		}

		// Hint: If placeholder data is an array, we assume that it's a link and the placeholder_data are the link attributes.
		if ( is_array( $placeholder_data ) && ! empty( $placeholder_data ) ) {

			// Build link.
			$link_text            = get_option( sprintf( 'wc_bis_%s_link_text', $key ) );
			$link_text_default_fn = sprintf( 'wc_bis_get_%s_link_default_text', $key );
			if ( empty( $link_text ) && function_exists( $link_text_default_fn ) ) {
				$link_text = $link_text_default_fn();
			}

			$defaults = array(
				'href'  => '#',
				'class' => '',
			);

			$link_attributes = wp_parse_args( $placeholder_data, $defaults );

			/**
			 * Filter: woocommerce_bis_link_attributes
			 *
			 * @since 9.9.0
			 * @param array $link_attributes The link attributes.
			 */
			$link_attributes = (array) apply_filters( sprintf( 'woocommerce_bis_%s_link_attributes', $key ), $link_attributes, $key );

			$link_attributes_string = implode(
				' ',
				array_map(
					function ( $key, $value ) {
						if ( empty( $value ) ) {
							return '';
						}
						return $key . '="' . esc_attr( $value ) . '"';
					},
					array_keys( $link_attributes ),
					$link_attributes
				)
			);

			$link_html = sprintf(
				'<a %1$s>%2$s</a>',
				$link_attributes_string,
				esc_html( $link_text )
			);

			$text = str_replace( $placeholder, $link_html, $text );

		} elseif ( is_scalar( $placeholder_data ) ) {
			$text = str_replace( $placeholder, $placeholder_data, $text );
		}

		return $text;
	}
}

if ( ! function_exists( 'wc_bis_wp_theme_get_element_class_name' ) ) {
	/**
	 * Compatibility wrapper for getting the element-based block class.
	 *
	 * @since 1.6.0
	 *
	 * @param  string $element The element.
	 * @return string
	 */
	function wc_bis_wp_theme_get_element_class_name( $element ) {
		return function_exists( 'wc_wp_theme_get_element_class_name' ) ? wc_wp_theme_get_element_class_name( $element ) : '';
	}
}
