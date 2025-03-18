/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import edit from './edit';
import icon from './icon';

const blockConfig = {
	...metadata,
	icon,
	edit,
	save,
};

if ( isExperimentalBlocksEnabled() ) {
	registerProductBlockType( blockConfig, {
		isAvailableOnPostEditor: true,
	} );
}
