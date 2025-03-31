/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { currencyDollar, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import metadata from './block.json';

const blockConfig = {
	...metadata,
	...sharedConfig,
	icon: (
		<Icon
			icon={ currencyDollar }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
