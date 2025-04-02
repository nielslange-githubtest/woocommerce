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
	const loadTimes: {
		title: string | null | undefined;
		serverResponse: number;
		firstPaint: number;
		domContentLoaded: number;
		loaded: number;
	}[] = await page.evaluate( ( templates ) => {
		const getLoadingMetrics = ( contentWindow: Window ) => {
			const navigationEntries =
				contentWindow.performance.getEntriesByType( 'navigation' );
			if ( navigationEntries.length === 0 ) {
				return null; // Signal that navigation timing is not ready
			}

			const paintTimings =
				contentWindow.performance.getEntriesByType( 'paint' );
			if ( paintTimings.length === 0 ) {
				return null; // Signal that paint timings are not ready
			}

			const [
				{
					requestStart,
					responseStart,
					responseEnd,
					domContentLoadedEventEnd,
					loadEventEnd,
				},
			] = navigationEntries as PerformanceNavigationTiming[];

			const firstPaintStartTime = paintTimings.find(
				( { name } ) => name === 'first-paint'
			)?.startTime;

			const firstContentfulPaintStartTime = paintTimings.find(
				( { name } ) => name === 'first-contentful-paint'
			)?.startTime;

			if ( ! firstPaintStartTime || ! firstContentfulPaintStartTime ) {
				return null; // Signal that paint metrics are not complete
			}

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
				timeSinceResponseEnd:
					contentWindow.performance.now() - responseEnd,
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

			const times: any[] = new Array( iframes.length );
			let loadedCount = 0;

			// Add timeout safety
			const timeout = setTimeout( () => {
				console.warn( 'Timeout waiting for iframes to complete' );
				resolve( times );
			}, 30000 );

			const processIframe = (
				iframe: HTMLIFrameElement,
				index: number
			) => {
				try {
					if ( ! iframe.contentWindow ) {
						console.warn(
							`No contentWindow for iframe ${ templates[ index ].title }`
						);
						return false;
					}

					const metrics = getLoadingMetrics( iframe.contentWindow );
					if ( ! metrics ) {
						console.log(
							`Waiting for performance metrics for ${ templates[ index ].title }`
						);
						return false; // Not ready yet, will try again
					}

					times[ index ] = {
						...metrics,
						title: templates[ index ].title,
					};
					loadedCount++;

					console.log(
						`Processed iframe ${ templates[ index ].title } (${ loadedCount }/${ iframes.length })`
					);

					if ( loadedCount === iframes.length ) {
						clearTimeout( timeout );
						resolve( times );
					}
					return true;
				} catch ( error ) {
					console.warn(
						`Error processing iframe ${ templates[ index ].title }:`,
						error
					);
					return false;
				}
			};

			// First pass: process all complete iframes
			iframes.forEach( ( iframe, index ) => {
				if (
					iframe.contentWindow?.document.readyState === 'complete'
				) {
					processIframe( iframe, index );
				}
			} );
		} );
	}, templatesInfo );

	return loadTimes;
}

test.describe( 'All templates performance', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );

	test( 'Loading', async ( { page }, testInfo ) => {
		const results: {
			title: string | null | undefined;
			serverResponse: number;
			firstPaint: number;
			domContentLoaded: number;
			loaded: number;
			requestCount: number;
		}[] = [];
		const samples = 5;
		const throwaway = 1;
		const iterations = samples + throwaway;

		for ( let i = 0; i < iterations; i++ ) {
			let requestCount = 0;
			page.on( 'request', () => {
				requestCount++;
			} );
			await page.goto(
				'/wp-admin/site-editor.php?postType=wp_template&activeView=WooCommerce'
			);

			const frames = page.locator( 'iframe[title="Editor canvas"]' );

			await expect( frames ).toHaveCount( 11 );

			for ( const frame of await frames.all() ) {
				const frameHandle = frame.contentFrame();
				await frameHandle.owner().evaluate( () => {
					return document.readyState === 'complete';
				} );
			}

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

						return {
							title,
							iframeSelector: `iframe[src="${ src }"]`,
						};
					} )
				);

			if ( i > throwaway ) {
				const loadTimes = await measureMultipleIframesLoadingTime(
					page,
					templates
				);

				// console.log( loadTimes, 'loadTimes' );

				const fixedLoadTimes = loadTimes.map( ( loadTime ) => {
					return {
						...loadTime,
						requestCount,
					};
				} );

				results.push( ...fixedLoadTimes );
			}

			// console.log( results );
		}
		const valuesByKeys = results.reduce( ( acc, curr ) => {
			const title = curr.title;
			if ( ! title ) {
				return acc;
			}
			acc[ title ] = {
				...acc[ title ],
				serverResponse: [
					...( acc[ title ]?.serverResponse || [] ),
					curr.serverResponse,
				],
				firstPaint: [
					...( acc[ title ]?.firstPaint || [] ),
					curr.firstPaint,
				],
				domContentLoaded: [
					...( acc[ title ]?.domContentLoaded || [] ),
					curr.domContentLoaded,
				],
				loaded: [ ...( acc[ title ]?.loaded || [] ), curr.loaded ],
				requestCount: [
					...( acc[ title ]?.requestCount || [] ),
					curr.requestCount,
				],
			};
			return acc;
		}, {} );

		const median = {};
		for ( const title in valuesByKeys ) {
			const values = valuesByKeys[ title ];
			median[ title ] = {}; // Initialize an object for this title

			for ( const key in values ) {
				values[ key ].sort( ( a, b ) => a - b );
				median[ title ][ key ] =
					values[ key ][ Math.floor( values[ key ].length / 2 ) ];
			}
		}

		await testInfo.attach( 'all-templates.json', {
			body: JSON.stringify( median, null, 2 ),
			contentType: 'application/json',
		} );

		console.log( JSON.stringify( median, null, 2 ) );

		expect( true ).toBe( true );
	} );
} );
