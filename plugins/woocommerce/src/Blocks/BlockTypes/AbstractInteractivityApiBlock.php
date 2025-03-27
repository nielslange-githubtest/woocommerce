<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AbstractInteractivityAPIBlock class.
 */
abstract class AbstractInteractivityAPIBlock extends AbstractBlock {

	/**
	 * The default render_callback for all blocks. This will ensure assets are enqueued just in time, then render
	 * the block (if applicable).
	 *
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string         $content    Block content. Default empty string.
	 * @param WP_Block|null  $block      Block instance.
	 * @return string Rendered block type output.
	 */
	public function render_callback( $attributes = [], $content = '', $block = null ) {
		$render_callback_attributes = $this->parse_render_callback_attributes( $attributes );
		$result                     = $this->render( $render_callback_attributes, $content, $block );

		if ( ! empty( $result ) ) {
			wp_enqueue_script_module( $this->get_full_block_name() );
		}

		return $result;
	}

	/**
	 * This block uses script modules, which are registered in AssetsController.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]|null
	 */
	protected function get_block_type_style() {
		$style_handle = $this->get_full_block_name() . '-style';
		$this->asset_api->register_style( $style_handle, $this->asset_api->get_block_asset_build_path( $style_handle, 'css' ), [], 'all', true );

		return [ 'wc-blocks-style', $style_handle ];
	}
}
