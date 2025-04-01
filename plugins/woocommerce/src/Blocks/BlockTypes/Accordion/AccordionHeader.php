<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\Accordion;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractInteractivityAPIBlock;

/**
 * AccordionHeader class.
 */
class AccordionHeader extends AbstractInteractivityAPIBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'accordion-header';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]|null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Disable the legacy editor style for this block type.
	 *
	 * @return null
	 */
	protected function get_legacy_editor_styles() {
		return null;
	}
}
