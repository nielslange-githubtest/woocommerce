/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { addQueryArgs } from '@wordpress/url';
import { sanitize } from 'dompurify';
export function isGutenbergVersionAtLeast( version: number ) {
	const adminSettings: { gutenberg_version?: string } = getSetting( 'admin' );
	if ( adminSettings.gutenberg_version ) {
		return parseFloat( adminSettings?.gutenberg_version ) >= version;
	}
	return false;
}

const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'code',
	'mark',
	'sub',
	'sup',
	'pre',
	'span',
	'ul',
	'ol',
	'li',
	'blockquote',
	'hr',
];
const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download', 'title' ];

/**
 * Sanitizes HTML content to ensure it only contains allowed tags and attributes.
 *
 * @param html - The HTML content to sanitize.
 * @return Sanitized HTML content.
 */
export function sanitizeHTML( html: string ) {
	return sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } );
}

/**
 * Gets the primary legacy sidebar items.
 *
 * @param settingsData - The settings data.
 * @param activePage - The active page.
 * @return The primary legacy sidebar items.
 */
export const getPrimaryLegacySidebarItems = (
	settingsData: SettingsData,
	activePage: string
) => {
	return Object.keys( settingsData.pages ).map( ( slug ) => {
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
	} );
};

/**
 * Gets the secondary legacy sidebar items.
 *
 * @param settingsData - The settings data.
 * @param activePage - The active page.
 * @param activeSection - The active section.
 * @return The secondary legacy sidebar items.
 */
export const getSecondaryLegacySidebarItems = (
	settingsData: SettingsData,
	activePage: string,
	activeSection: string
) => {
	return Object.keys( settingsData.pages[ activePage ].sections ).map(
		( slug ) => {
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
		}
	);
};
