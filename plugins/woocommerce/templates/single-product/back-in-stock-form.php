<?php
/**
 * Back in Stock Form
 *
 * Shows the additional form fields on the product page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/back-in-stock-form.php.
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

/**
 * Hook: woocommerce_bis_before_form.
 *
 * @since 9.9.0
 *
 * @param WC_Product $product The product object.
 */
do_action( 'woocommerce_bis_before_form', $product );
?><div id="wc_bis_product_form" data-bis-product-id="<?php echo $product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ); ?>">

	<p class="wc_bis_form_title"><?php echo wp_kses_post( $header_text ); ?></p>

	<?php
	/**
	 * Hook: woocommerce_bis_before_form_fields.
	 *
	 * @since 9.9.0
	 *
	 * @param WC_Product $product The product object.
	 */
	do_action( 'woocommerce_bis_before_form_fields', $product );
	?>

	<?php if ( ! is_user_logged_in() && ! wc_bis_is_account_required() ) : ?>
		<input type="text" id="wc_bis_email" name="wc_bis_email" class="input-text" placeholder="<?php echo esc_attr__( 'Enter your e-mail', 'woocommerce' ); ?>" />
	<?php endif; ?>

	<button class="<?php echo esc_attr( $button_class ); ?>" type="button" id="wc_bis_send_form" name="wc_bis_send_form">
		<?php echo esc_html( $button_text ); ?>
	</button>

	<?php if ( $show_count ) : ?>
		<div class="wc_bis_registrations_count">
			<?php echo wp_kses_post( $count_text ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! is_user_logged_in() && wc_bis_is_opt_in_required() && ! wc_bis_is_account_required() ) : ?>
		<label for="wc_bis_opt_in" class="wc_bis_opt_in">
			<input type="checkbox" name="wc_bis_opt_in" id="wc_bis_opt_in" />
				<span class="wc_bis_opt_in__text">
					<?php echo wp_kses_post( $opt_in_text ); ?>
				</span>
		</label>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_bis_after_form_fields.
	 *
	 * @since 9.9.0
	 *
	 * @param WC_Product $product The product object.
	 */
	do_action( 'woocommerce_bis_after_form_fields', $product );
	?>
</div>
<?php

/**
 * Hook: woocommerce_bis_after_form.
 *
 * @since 9.9.0
 *
 * @param WC_Product $product The product object.
 */
do_action( 'woocommerce_bis_after_form', $product );
