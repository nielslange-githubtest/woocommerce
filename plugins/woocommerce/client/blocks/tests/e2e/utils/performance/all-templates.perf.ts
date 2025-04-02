/**
 * External dependencies
 */

import { Page } from '@playwright/test';
import { test, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';
import * as fs from 'fs';

type TemplateInfo = {
	title: string | null | undefined;
	iframeSelector: string | null | undefined;
};

async function measureMultipleIframesLoadingTime(
	page: Page,
	templatesInfo: TemplateInfo[]
) {
	const loadTimes = await page.evaluate( ( templates ) => {
		const getLoadingMetrics = () => {
			const [
				{
					requestStart,
					responseStart,
					responseEnd,
					domContentLoadedEventEnd,
					loadEventEnd,
				},
			] = performance.getEntriesByType(
				'navigation'
			) as PerformanceNavigationTiming[];
			const paintTimings = performance.getEntriesByType(
				'paint'
			) as PerformancePaintTiming[];

			const firstPaintStartTime = paintTimings.find(
				( { name } ) => name === 'first-paint'
			)!.startTime;

			const firstContentfulPaintStartTime = paintTimings.find(
				( { name } ) => name === 'first-contentful-paint'
			)!.startTime;

			return {
				// Server side metric.
				serverResponse: responseStart - requestStart,
				// For client side metrics, consider the end of the response (the
				// browser receives the HTML) as the start time (0).
				firstPaint: firstPaintStartTime - responseEnd,
				domContentLoaded: domContentLoadedEventEnd - responseEnd,
				loaded: loadEventEnd - responseEnd,
				firstContentfulPaint:
					firstContentfulPaintStartTime - responseEnd,
				timeSinceResponseEnd: performance.now() - responseEnd,
			};
		};
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

			const times: [] = [];

			let loadedCount = 0;

			iframes.forEach( ( iframe: HTMLIFrameElement, index ) => {
				if (
					iframe.contentWindow?.document.readyState === 'complete'
				) {
					times[ index ] = {
						...getLoadingMetrics(),
						title: templates[ index ].title,
					};
					loadedCount++;

					if ( loadedCount === iframes.length ) {
						resolve( times );
					}
				} else {
					// If iframe is still loading
					iframe.addEventListener( 'load', () => {
						times[ index ] = {
							...getLoadingMetrics(),
							title: templates[ index ].title,
						};
						loadedCount++;

						if ( loadedCount === iframes.length ) {
							resolve( times );
						}
					} );
				}
			} );
			return { times };
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

		fs.appendFileSync(
			'./performance-report.json',
			JSON.stringify( loadTimes )
		);

		expect( loadTimes ).toBeDefined();
	} );
} );
