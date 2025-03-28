/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

import {
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';

export const Header = ( { pageTitle = '' }: { pageTitle?: string } ) => {
	return (
		<VStack
			className="woocommerce-settings-header woocommerce-site-page-header"
			as="header"
			spacing={ 0 }
		>
			<HStack className="woocommerce-site-page-header__page-title">
				<Heading
					as="h2"
					level={ 3 }
					weight={ 500 }
					className="woocommerce-site-page-header__title"
					truncate
				>
					{ pageTitle }
				</Heading>
			</HStack>
		</VStack>
	);
};
