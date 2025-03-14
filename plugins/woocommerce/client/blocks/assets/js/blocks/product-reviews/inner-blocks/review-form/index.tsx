/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { postCommentsForm as icon } from '@wordpress/icons';
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

if ( isExperimentalBlocksEnabled() ) {
	// @ts-expect-error metadata is not typed.
	registerBlockType( metadata, {
		icon,
		edit,
	} );
}
