/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useMemo,
	useState,
	useRef,
	useContext,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	addAction,
	applyFilters,
	didFilter,
	removeAction,
} from '@wordpress/hooks';
import { useLocation } from '@automattic/site-admin';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { Sidebar } from './components';
import { Route } from './types';
import { LegacyContent } from './legacy';
import { SettingsDataContext } from './data';

const NotFound = () => {
	return (
		<div className="woocommerce-settings-content">
			<h1>{ __( 'Page not found', 'woocommerce' ) }</h1>
		</div>
	);
};

/**
 * Default route when active page is not found.
 *
 * @param {string}        activePage - The active page.
 * @param {settingsPages} settingsPages      - The settings pages.
 */
const getNotFoundRoute = ( activePage: string ): Route => ( {
	key: activePage,
	areas: {
		sidebar: (
			<Sidebar
				routeKey={ activePage }
				sidebarItems={ [] }
				currentNestLevel={ 1 }
			/>
		),
		content: <NotFound />,
		edit: null,
	},
	widths: {
		content: undefined,
		edit: undefined,
	},
} );

/**
 * Creates a route configuration for legacy settings.
 *
 * @param {string}       activePage    - The active page.
 * @param {string}       activeSection - The active section.
 * @param {settingsPage} settingsPage  - The settings page.
 * @param {settingsData} settingsData  - The settings data.
 */
const getLegacyRoute = (
	activePage: string,
	activeSection: string,
	settingsPage: SettingsPage,
	settingsData: SettingsData
): Route => {
	const isPrimary =
		activeSection === 'default' &&
		Object.keys( settingsData.pages[ activePage ].sections ).length === 1;

	const primarySidebarItems = Object.keys( settingsData.pages ).map(
		( slug ) => {
			const {
				label,
				icon = 'settings',
				sections,
			} = settingsData.pages[ slug ];
			const to = addQueryArgs( 'wc-settings', { tab: slug } );
			const withChevron = Object.keys( sections ).length > 1;
			const isCurrent = slug === activePage;

			return {
				slug,
				label,
				icon,
				to,
				withChevron,
				isCurrent,
			};
		}
	);

	const secondarySidebarItems = Object.keys(
		settingsData.pages[ activePage ].sections
	).map( ( slug ) => {
		const page = settingsData.pages[ activePage ];
		const { label } = page.sections[ slug ];
		const to = addQueryArgs( 'wc-settings', {
			tab: activePage,
			section: slug,
		} );
		const isCurrent = slug === activeSection;
		const backPath = addQueryArgs( 'wc-settings', {} );
		const backLabel = page.label;
		return {
			slug,
			label,
			to,
			withChevron: false,
			isCurrent,
			backPath,
			backLabel,
		};
	} );

	const sidebarItems = isPrimary
		? primarySidebarItems
		: secondarySidebarItems;

	const key = activePage + '-' + activeSection;

	return {
		key,
		areas: {
			sidebar: (
				<Sidebar
					routeKey={ key }
					sidebarItems={ sidebarItems }
					currentNestLevel={ isPrimary ? 1 : 2 }
				/>
			),
			content: (
				<LegacyContent
					settingsData={ settingsData }
					settingsPage={ settingsPage }
					activeSection={ activeSection }
				/>
			),
			edit: null,
		},
		widths: {
			content: undefined,
			edit: undefined,
		},
	};
};

const PAGES_FILTER = 'woocommerce_admin_settings_pages';

const getModernPages = () => {
	/**
	 * Get the modern settings pages.
	 *
	 * @return {Record<string, Route>} The pages.
	 */
	return applyFilters( PAGES_FILTER, {} ) as Record< string, Route >;
};

/**
 * Hook to get the modern settings pages.
 *
 * @return {Record<string, Route>} The pages.
 */
export function useModernRoutes(): Record< string, Route > {
	const [ routes, setRoutes ] = useState< Record< string, Route > >(
		getModernPages()
	);
	const location = useLocation();
	const isFirstRender = useRef( true );

	/*
	 * Handler for new pages being added after the initial filter has been run,
	 * so that if any routing pages are added later, they can still be rendered
	 * instead of falling back to the `NoMatch` page.
	 */
	useEffect( () => {
		const handleHookAdded = ( hookName: string ) => {
			if ( hookName !== PAGES_FILTER ) {
				return;
			}

			const filterCount = didFilter( PAGES_FILTER );
			if ( filterCount && filterCount > 0 ) {
				setRoutes( getModernPages() );
			}
		};

		const namespace = `woocommerce/woocommerce/watch_${ PAGES_FILTER }`;
		addAction( 'hookAdded', namespace, handleHookAdded );

		return () => {
			removeAction( 'hookAdded', namespace );
		};
	}, [] );

	// Update modern pages when the location changes.
	useEffect( () => {
		if ( isFirstRender.current ) {
			// Prevent updating routes again on first render.
			isFirstRender.current = false;
			return;
		}

		setRoutes( getModernPages() );
	}, [ location.query ] );

	return routes;
}

/**
 * Hook to determine and return the active route based on the current path.
 */
export const useActiveRoute = (): {
	route: Route;
	settingsPage?: SettingsPage;
	activePage?: string;
	activeSection?: string;
} => {
	const { settingsData } = useContext( SettingsDataContext );
	const location = useLocation();
	const modernRoutes = useModernRoutes();

	return useMemo( () => {
		const { tab: activePage = 'general', section: activeSection } =
			location.query || {};
		const settingsPage = settingsData?.pages?.[ activePage ];

		if ( ! settingsPage ) {
			return {
				route: getNotFoundRoute( activePage, settingsData.pages ),
			};
		}

		// Handle legacy pages.
		if ( ! settingsPage.is_modern ) {
			return {
				route: getLegacyRoute(
					activePage,
					activeSection || 'default',
					settingsPage,
					settingsData
				),
				settingsPage,
				activePage,
				activeSection,
			};
		}

		const modernRoute = modernRoutes[ activePage ];

		// Handle modern pages.
		if ( ! modernRoute ) {
			return {
				route: getNotFoundRoute( activePage, settingsData.pages ),
			};
		}

		// Sidebar is responsibility of WooCommerce, not extensions so add it here.
		modernRoute.areas.sidebar = (
			<Sidebar
				activePage={ activePage }
				pages={ settingsData.pages }
				pageTitle={ __( 'Store settings', 'woocommerce' ) }
			/>
		);
		// Make sure we have a key.
		modernRoute.key = activePage;

		return {
			route: modernRoute,
			settingsPage,
			activePage,
			activeSection,
		};
	}, [ settingsData, location.query ] );
};
