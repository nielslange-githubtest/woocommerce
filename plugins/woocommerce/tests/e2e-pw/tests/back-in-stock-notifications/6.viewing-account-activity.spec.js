/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import AcceptanceHelper from './helper';
const { test, request } = require( '@playwright/test' );
const { CUSTOMER_STATE_PATH } = require( '../../playwright.config' );

test.describe( 'Feature: Viewing Account Activity', () => {
	let helper;

	test.use( { storageState: CUSTOMER_STATE_PATH } );
	test.beforeEach( async ( { baseURL, page } ) => {
		await setOption( request, baseURL, 'woocommerce_coming_soon', 'no' );
		await setOption( request, baseURL, 'wc_bis_account_required', 'no' );
		await setOption( request, baseURL, 'wc_bis_opt_in_required', 'no' );
		await setOption(
			request,
			baseURL,
			'wc_bis_double_opt_in_required',
			'no'
		);
		helper = new AcceptanceHelper( baseURL, page );
	} );
	test.afterEach( async ( {} ) => {
		helper.deleteCurrentProduct();
	} );
	test( 'View triggered notifications activity', async () => {
		const { given, when, then } = helper;
		await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();

		await when.iClickTheNotifyMeButton();
		await when.iAmOnTheStockNotificationsAccountPage();

		await then.iSeeSomeActivityRelatedWithNotificationsISignedUpToReceiveInThePast();
	} );
} );
