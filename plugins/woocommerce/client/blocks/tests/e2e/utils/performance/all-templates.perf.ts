/**
 * External dependencies
 */

import { Page } from '@playwright/test';
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

type TemplateInfo = {
	title: string | null | undefined;
	iframeSelector: string | null | undefined;
};

async function measureMultipleIframesLoadingTime(
	page: Page,
	templatesInfo: TemplateInfo[]
) {
	const loadTimes = await page.evaluate( ( templates: TemplateInfo[] ) => {
		const totalRequests = performance.getEntriesByType( 'resource' ).length;
		return new Promise( ( resolve ) => {
			const iframes = templates
				.map( ( { iframeSelector } ) =>
					document.querySelector( iframeSelector )
				)
				.filter( ( iframe ) => iframe !== null ) as HTMLIFrameElement[];
			if ( ! iframes.length ) {
				resolve( [] );
				return;
			}

			const times: {
				time: number;
				requestNumbers: number;
				title: string | null | undefined;
			}[] = [];

			let loadedCount = 0;

			iframes.forEach( ( iframe: HTMLIFrameElement, index ) => {
				if (
					iframe.contentWindow?.document.readyState === 'complete'
				) {
					// If iframe is already loaded
					const navigationEntry =
						iframe.contentWindow.performance.getEntriesByType(
							'navigation'
						)[ 0 ];
					times[ index ] = {
						time:
							navigationEntry.loadEventEnd -
							navigationEntry.startTime,
						title: templates[ index ].title,
						requestNumbers:
							iframe.contentWindow.performance.getEntriesByType(
								'resource'
							).length,
					};
					loadedCount++;

					if ( loadedCount === iframes.length ) {
						resolve( times );
					}
				} else {
					// If iframe is still loading
					iframe.addEventListener( 'load', () => {
						const navigationEntry =
							iframe?.contentWindow.performance.getEntriesByType(
								'navigation'
							)[ 0 ];
						times[ index ] = {
							time:
								navigationEntry.loadEventEnd -
								navigationEntry.startTime,
							title: templates[ index ].title,
							requestNumbers:
								iframe.contentWindow.performance.getEntriesByType(
									'resource'
								).length,
						};
						loadedCount++;

						if ( loadedCount === iframes.length ) {
							resolve( times );
						}
					} );
				}
			} );
			return { times, totalRequests };
		} );
	}, templatesInfo );

	return { loadTimes };
}

test.describe( 'All templates performance', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );

	test( 'Loading', async ( { page } ) => {
		await page.goto(
			'/wp-admin/site-editor.php?postType=wp_template&activeView=WooCommerce'
		);

		await page.waitForSelector( 'iframe[title="Editor canvas"]' );

		const templates = await page
			.locator( '.dataviews-view-grid__card' )
			.evaluateAll( ( elements ) =>
				elements.map( ( element ) => {
					const title = element.querySelector(
						'.fields-field__title'
					)?.textContent;
					const src = element
						.querySelector( 'iframe' )
						?.getAttribute( 'src' );

					return { title, iframeSelector: `iframe[src="${ src }"]` };
				} )
			);

		const loadTimes = await measureMultipleIframesLoadingTime(
			page,
			templates
		);

		console.log( loadTimes );

		expect( loadTimes ).toBeDefined();
	} );
} );
