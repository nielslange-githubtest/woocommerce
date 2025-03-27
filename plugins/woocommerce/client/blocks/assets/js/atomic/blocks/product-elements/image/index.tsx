/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import { supports } from './supports';
import sharedConfig from '../shared/config';
import metadata from './block.json';
import deprecated from './deprecated';

registerBlockType( metadata, {
	...sharedConfig,
	icon,
	supports,
	edit,
	save: () => {
		return <InnerBlocks.Content />;
	},
	deprecated,
} );
