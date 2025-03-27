/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { __experimentalItemGroup as ItemGroup } from '@wordpress/components';
import * as IconPackage from '@wordpress/icons';
import {
	SidebarNavigationScreen,
	SidebarNavigationItem,
} from '@automattic/site-admin';

const { Icon, ...icons } = IconPackage;

export const Sidebar = ( {
	pageTitle,
	sidebarItems,
}: {
	pageTitle: string;
	sidebarItems: Array< {
		slug: string;
		label: string;
		to: string;
		isCurrent: boolean;
		withChevron: boolean;
		icon?: string;
	} >;
} ) => {
	return (
		<SidebarNavigationScreen
			title={ pageTitle }
			isRoot
			exitLink={ addQueryArgs( 'admin.php', { page: 'wc-admin' } ) }
			content={
				<ItemGroup>
					{ sidebarItems.map( ( item ) => {
						const {
							label,
							icon,
							isCurrent,
							to,
							withChevron,
							slug,
						} = item;

						return (
							<SidebarNavigationItem
								icon={ icons[ icon as keyof typeof icons ] }
								aria-current={ isCurrent }
								uid={ slug }
								key={ slug }
								to={ to }
								suffix={ withChevron ? 'CHEVRON' : undefined }
							>
								{ label }
							</SidebarNavigationItem>
						);
					} ) }
				</ItemGroup>
			}
		/>
	);
};
