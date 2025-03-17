/* eslint-disable playwright/expect-expect */
/**
 * External dependencies
 */
import { test as base } from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from '../checkout/constants';

interface LoadingDurations {
	serverResponse: number;
	firstPaint: number;
	domContentLoaded: number;
	loaded: number;
	firstContentfulPaint: number;
	firstBlock: number;
}

interface PageObject {
	page: Page;
}

const test = base.extend< { pageObject: PageObject } >( {
	pageObject: async ( { page }, use ) => {
		await use( { page } );
	},
} );

test.describe( 'Product Gallery Performance', () => {
	test.beforeEach( async ( { frontendUtils } ) => {
		// Navigate to a product page that has a gallery
		await frontendUtils.goToShop();
		await frontendUtils.page.click(
			`text=${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
		);
	} );

	test( 'Loading Performance', async ( { page, performanceUtils } ) => {
		const results = {
			serverResponse: [] as number[],
			firstPaint: [] as number[],
			domContentLoaded: [] as number[],
			loaded: [] as number[],
			firstContentfulPaint: [] as number[],
			firstBlock: [] as number[],
		};

		// Test loading performance multiple times
		let i = 3;
		while ( i-- ) {
			await page.reload();
			await page.locator( '.wc-block-product-gallery' ).waitFor();
			const durations =
				( await performanceUtils.getLoadingDurations() ) as LoadingDurations;

			// Convert to milliseconds
			results.serverResponse.push( durations.serverResponse * 1000 );
			results.firstPaint.push( durations.firstPaint * 1000 );
			results.domContentLoaded.push( durations.domContentLoaded * 1000 );
			results.loaded.push( durations.loaded * 1000 );
			results.firstContentfulPaint.push(
				durations.firstContentfulPaint * 1000
			);
			results.firstBlock.push( durations.firstBlock * 1000 );
		}

		// Log performance results
		Object.entries( results ).forEach( ( [ name, value ] ) => {
			if (
				Array.isArray( value ) &&
				value.every( ( x ) => typeof x === 'number' ) &&
				value.length > 0
			) {
				performanceUtils.logPerformanceResult(
					`Product Gallery block loading: (${ name })`,
					value
				);
			}
		} );
	} );

	test( 'Gallery Image Navigation Performance', async ( {
		page,
		performanceUtils,
	} ) => {
		await page.locator( '.wc-block-product-gallery' ).waitFor();

		const timesForImageSwitch: number[] = [];
		let i = 3;
		while ( i-- ) {
			// Find all thumbnail images
			const thumbnails = await page
				.locator( '.wc-block-product-gallery-thumbnails img' )
				.all();
			if ( thumbnails.length > 1 ) {
				const start = performance.now();

				// Click the second thumbnail
				await thumbnails[ 1 ].click();

				// Wait for the main image to update
				await page.waitForSelector( '[data-selected-image-id]' );

				const end = performance.now();
				timesForImageSwitch.push( end - start );
			}
		}

		performanceUtils.logPerformanceResult(
			'Product Gallery: Image Switch Time',
			timesForImageSwitch
		);
	} );

	test( 'Gallery Dialog Open Performance', async ( {
		page,
		performanceUtils,
	} ) => {
		await page.locator( '.wc-block-product-gallery' ).waitFor();

		const timesForDialogOpen: number[] = [];
		let i = 3;
		while ( i-- ) {
			// Click the main image to open dialog
			const mainImage = page.locator(
				'.wc-block-product-gallery__main-image'
			);

			const start = performance.now();

			await mainImage.click();

			// Wait for dialog to be visible
			await page.locator( 'dialog[open]' ).waitFor();

			const end = performance.now();
			timesForDialogOpen.push( end - start );

			// Close dialog for next iteration
			await page.keyboard.press( 'Escape' );
			await page.locator( 'dialog:not([open])' ).waitFor();
		}

		performanceUtils.logPerformanceResult(
			'Product Gallery: Dialog Open Time',
			timesForDialogOpen
		);
	} );

	test( 'Gallery Image Drag Performance', async ( {
		page,
		performanceUtils,
	} ) => {
		await page.locator( '.wc-block-product-gallery' ).waitFor();

		const timesForDrag: number[] = [];
		let i = 3;
		while ( i-- ) {
			const gallery = page.locator(
				'.wc-block-product-gallery__main-image'
			);
			const box = await gallery.boundingBox();

			if ( box ) {
				const start = performance.now();

				// Simulate drag from center to left
				await page.mouse.move(
					box.x + box.width / 2,
					box.y + box.height / 2
				);
				await page.mouse.down();
				await page.mouse.move( box.x, box.y + box.height / 2, {
					steps: 10,
				} );
				await page.mouse.up();

				// Wait for the drag animation to complete
				await page
					.locator( '.wc-block-product-gallery__main-image' )
					.waitFor();

				const end = performance.now();
				timesForDrag.push( end - start );
			}
		}

		performanceUtils.logPerformanceResult(
			'Product Gallery: Image Drag Time',
			timesForDrag
		);
	} );
} );
