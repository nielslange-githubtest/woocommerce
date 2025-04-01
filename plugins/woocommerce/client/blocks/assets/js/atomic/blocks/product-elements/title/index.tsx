/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { heading, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import { Save } from './save';
import metadata from './block.json';

export const icon = (
	<Icon icon={ heading } className="wc-block-editor-components-block-icon" />
);

registerBlockType( metadata, {
	icon: {
		src: icon,
	},
	edit,
	save: Save,
} );
