/**
 * External dependencies
 */
import { Frame, Page } from '@playwright/test';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { median } from '../utils';

async function getFrameMetrics( frame: Frame ) {
	return await frame.evaluate( () => {
		const navigationEntries =
			window.performance.getEntriesByType( 'navigation' );
		const paintTimings = window.performance.getEntriesByType( 'paint' );

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
			frameId: window.location.href,
			// Server side metric.
			serverResponse: responseStart - requestStart,
			// For client side metrics, consider the end of the response (the
			// browser receives the HTML) as the start time (0).
			firstPaint: firstPaintStartTime - responseEnd,
			domContentLoaded: domContentLoadedEventEnd - responseEnd,
			loaded: loadEventEnd - responseEnd,
			firstContentfulPaint: firstContentfulPaintStartTime - responseEnd,
			timeSinceResponseEnd: window.performance.now() - responseEnd,
		};
	} );
}

test.describe( 'All templates performance', () => {
	test.afterAll( async ( {}, testInfo ) => {
		const medians = {};
		Object.keys( results ).forEach( ( metric ) => {
			medians[ metric ] = median( results[ metric ] );
		} );

		console.log( medians, 'medians' );
		await testInfo.attach( 'results', {
			body: JSON.stringify( { 'all-templates': medians }, null, 2 ),
			contentType: 'application/json',
		} );
	} );

	const results = {};
	const samples = 3;
	const throwaway = 1;
	const iterations = samples + throwaway;

	for ( let i = 0; i < iterations; i++ ) {
		test( `Run the test (${ i } of ${ iterations })`, async ( {
			page,
		} ) => {
			let requestCount = 0;
			page.on( 'request', () => {
				requestCount++;
			} );

			await page.goto(
				'/wp-admin/site-editor.php?postType=wp_template&activeView=WooCommerce'
			);

			const mainFrame = page.mainFrame();
			let frames: Frame[] = [];

			await expect
				.poll( async () => {
					frames = mainFrame.childFrames();
					return frames.length;
				} )
				.toBe( 11 );

			const allFrameMetrics: ( {
				frameId: string;
				serverResponse: number;
				firstPaint: number;
				domContentLoaded: number;
				loaded: number;
				firstContentfulPaint: number;
				timeSinceResponseEnd: number;
			} | null )[] = [];

			await Promise.all(
				frames.map( async ( frame ) => {
					await frame.waitForLoadState( 'domcontentloaded' );
					const frameMetrics = await getFrameMetrics( frame );
					if ( ! frameMetrics ) {
						throw new Error( 'Could not get frame metrics' );
					}
					allFrameMetrics.push( frameMetrics );
				} )
			);

			if ( i > throwaway ) {
				allFrameMetrics.forEach( ( frameMetrics ) => {
					if ( ! frameMetrics ) return;
					Object.entries( frameMetrics ).forEach(
						( [ metric, value ] ) => {
							// Skip frameId as it's not a numeric metric
							if ( metric === 'frameId' ) return;

							if ( ! results[ metric ] ) {
								results[ metric ] = [];
							}
							results[ metric ].push( value );
						}
					);
				} );

				if ( ! results[ 'requestCount' ] ) {
					results[ 'requestCount' ] = [];
				}

				results[ 'requestCount' ].push( requestCount );
			}
		} );
	}
} );
