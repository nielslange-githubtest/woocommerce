/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import AcceptanceHelper from './helper';
import { activateTheme, DEFAULT_THEME } from '../../utils/themes';
const { test, request } = require( '@playwright/test' );

[ 'twentytwentyfour', 'storefront' ].forEach( ( theme ) => {
	test.describe( `Feature: Viewing Subscribers Count: ${ theme }`, () => {
		let helper;

		test.beforeAll( async ( { baseURL } ) => {
			await activateTheme( baseURL, theme );
		} );

		test.afterAll( async ( { baseURL } ) => {
			await activateTheme( baseURL, DEFAULT_THEME );
		} );

		test.beforeEach( async ( { baseURL, page } ) => {
			await setOption(
				request,
				baseURL,
				'woocommerce_coming_soon',
				'no'
			);
			await setOption(
				request,
				baseURL,
				'wc_bis_account_required',
				'no'
			);
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
		test( 'View number of customers who have joined the waitlist', async () => {
			const { given, when, then } = helper;
			await given.numberOfCustomerWhoHaveJoinedTheWaitlistIsVisible();
			await given.signUpsAreSingleOptInWithoutCheckbox();
			await given.iGoToTheProductPage();
			await when.iEnterMyEmail();
			await when.iClickTheNotifyMeButton();
			await then.iSeeThatMySignupRequestWasSuccessful();

			await when.iGoToTheProductPage();
			await then.iSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock();
			await then.iSeeASignUpButton();
			await then.iSeeThatSomeCustomersHaveAlreadySignedUp();
		} );
	} );
} );
