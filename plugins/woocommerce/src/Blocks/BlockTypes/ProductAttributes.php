<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductAttributes class.
 */
class ProductAttributes extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-attributes';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]|null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	public function render( $attributes, $content, $block ) {
		$product = wc_get_product( get_the_ID() );

		if ( ! $product ) {
			return '';
		}

		ob_start();

		?>
		<table class="woocommerce-product-attributes shop_attributes">
			<tbody>
				<?php
				// Display Weight if available.
				if ( $product->get_weight() ) {
					?>
					<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--weight">
						<th class="woocommerce-product-attributes-item__label">
							<?php echo esc_html__( 'Weight', 'woocommerce' ); ?>
						</th>
						<td class="woocommerce-product-attributes-item__value">
							<?php echo esc_html( $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit' ) ); ?>
						</td>
					</tr>
					<?php
				}

				// Display Dimensions if available.
				if ( $product->has_dimensions() ) {
					?>
					<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--dimensions">
						<th class="woocommerce-product-attributes-item__label">
							<?php echo esc_html__( 'Dimensions', 'woocommerce' ); ?>
						</th>
						<td class="woocommerce-product-attributes-item__value">
							<?php echo esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) ); ?>
						</td>
					</tr>
					<?php
				}

				// Display Product Attributes.
				$attributes = $product->get_attributes();
				foreach ( $attributes as $attribute ) {
					// Skip if attribute is not visible.
					if ( ! $attribute->get_visible() ) {
						continue;
					}

					$attribute_name = wc_attribute_label( $attribute->get_name() );
					?>
					<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--<?php echo esc_attr( sanitize_title( $attribute->get_name() ) ); ?>">
						<th class="woocommerce-product-attributes-item__label">
							<?php echo esc_html( $attribute_name ); ?>
						</th>
						<td class="woocommerce-product-attributes-item__value">
							<?php
							$values = array();
							if ( $attribute->is_taxonomy() ) {
								$attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

								foreach ( $attribute_values as $attribute_value ) {
									$values[] = esc_html( $attribute_value->name );
								}
							} else {
								$values = $attribute->get_options();
								foreach ( $values as &$value ) {
									$value = esc_html( $value );
								}
							}
							echo wp_kses_post( implode( ', ', $values ) );
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}
}
