/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import metadata from './block.json';
import { BLOCK_ICON as icon } from './constants';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	...metadata,
	icon: { src: icon },
	edit,
	save: () => null,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
