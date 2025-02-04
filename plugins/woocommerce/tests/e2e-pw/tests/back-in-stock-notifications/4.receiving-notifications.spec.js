/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import AcceptanceHelper from './helper';
const { test, request } = require( '@playwright/test' );
const { CUSTOMER_STATE_PATH } = require( '../../playwright.config' );
test.describe( 'Feature: Receiving Notifications', () => {
	let helper;

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
	test.use( { storageState: CUSTOMER_STATE_PATH } );
	test( 'Receive a simple product notification', async () => {
		const { given, when, then } = helper;
		await given.aSimpleProductThatIsOutOfStock();
		await given.theProductHasNotifications();

		await when.iViewTheNotificationIReceivedViaEmail();

		await when.iClickTheButtonPromptingMeToPurchaseTheProduct();

		await then.iAmTakenToTheProductPageToCompleteMyPurchase();
	} );
	test( 'Receive a variation notification', async () => {
		const { given, when, then } = helper;
		await given.aVariableProductThatContainsOutOfStockVariations();
		await given.theVariationHasNotifications();

		await when.iViewTheNotificationIReceivedViaEmail();

		await when.iClickTheButtonPromptingMeToPurchaseTheProduct();

		await then.iAmTakenToTheProductPageToCompleteMyPurchase();
		await then.iSeeThatTheVariationIHadSignedUpForIsPreSelected();
	} );
} );
