/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';

type Attribute = {
	attribute: string;
	value: string;
};

type Context = {
	variation: Attribute[];
};

store( 'woocommerce/add-to-cart-with-options', {
	state: {
		get variation() {
			const { variation } = getContext< Context >();
			return variation ?? [];
		},
	},
	actions: {
		setAttribute( attribute: string, value: string ) {
			const context = getContext< Context >();
			const index = context.variation.findIndex(
				( variation ) => variation.attribute === attribute
			);
			if ( index >= 0 ) {
				context.variation[ index ] = {
					attribute,
					value,
				};
			} else {
				context.variation.push( {
					attribute,
					value,
				} );
			}
		},
		removeAttribute( attribute: string ) {
			const context = getContext< Context >();
			const index = context.variation.findIndex(
				( variation ) => variation.attribute === attribute
			);
			if ( index >= 0 ) {
				context.variation.splice( index, 1 );
			}
		},
	},
} );
