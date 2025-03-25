/**
 * External dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

const { state } = store( 'woocommerce/mini-cart-drawer', {
	actions: {
		toggleDrawerOpen: async () => {
			const context = getContext< { drawerOpen: boolean } >();
			context.drawerOpen = ! context.drawerOpen;

			if ( context.drawerOpen ) {
				document.body.dispatchEvent(
					new Event( 'wc-mini-cart-interactivity-open-drawer' )
				);
			}
		},
	},
} );
