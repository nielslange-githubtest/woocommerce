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
}
