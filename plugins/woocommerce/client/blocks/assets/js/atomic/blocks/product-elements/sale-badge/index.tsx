/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { percent, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	...sharedConfig,
	icon: (
		<Icon
			icon={ percent }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
	ancestor: [
		...( sharedConfig.ancestor || [] ),
		'woocommerce/product-gallery',
	],
} );
