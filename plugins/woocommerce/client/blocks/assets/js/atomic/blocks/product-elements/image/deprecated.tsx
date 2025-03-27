/**
 * External dependencies
 */
import clsx from 'clsx';
import { createBlock } from '@wordpress/blocks';

const v1 = {
	attributes: {
		showProductLink: {
			type: 'boolean',
			default: true,
		},
		showSaleBadge: {
			type: 'boolean',
			default: true,
		},
		saleBadgeAlign: {
			type: 'string',
			default: 'right',
		},
		imageSizing: {
			type: 'string',
			default: 'single',
		},
		productId: {
			type: 'number',
			default: 0,
		},
		isDescendentOfQueryLoop: {
			type: 'boolean',
			default: false,
		},
		isDescendentOfSingleProductBlock: {
			type: 'boolean',
			default: false,
		},
		width: {
			type: 'string',
		},
		height: {
			type: 'string',
		},
		scale: {
			type: 'string',
			default: 'cover',
		},
		aspectRatio: {
			type: 'string',
		},
	},
	save( { attributes } ) {
		if (
			attributes.isDescendentOfQueryLoop ||
			attributes.isDescendentOfSingleProductBlock ||
			attributes.isDescendentOfSingleProductTemplate
		) {
			return null;
		}

		return <div className={ clsx( 'is-loading', attributes.className ) } />;
	},
	isEligible: ( attributes ) => attributes.showSaleBadge !== undefined,
	migrate: ( attributes ) => {
		// Remove the sale badge attributes and convert it to an inner block if it is set.
		const { showSaleBadge, saleBadgeAlign, ...restAttributes } = attributes;
		if ( ! showSaleBadge ) {
			return [ restAttributes ];
		}

		// Place the badge into a row so that we can set the alignment.
		const badgeRow = createBlock(
			'core/group',
			{
				layout: {
					type: 'flex',
					flexWrap: 'nowrap',
					justifyContent: saleBadgeAlign,
				},
			},
			[
				createBlock( 'woocommerce/product-sale-badge', {
					productId: attributes.productId,
				} ),
			]
		);

		return [ restAttributes, [ badgeRow ] ];
	},
};

export default [ v1 ];
