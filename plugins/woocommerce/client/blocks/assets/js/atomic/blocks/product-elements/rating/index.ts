/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	...metadata,
	icon: { src: icon },
	edit,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
