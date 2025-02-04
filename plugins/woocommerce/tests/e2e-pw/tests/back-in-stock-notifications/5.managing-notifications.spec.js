/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import { activateTheme } from '../../utils/themes';
import AcceptanceHelper from './helper';
const { test, request } = require( '@playwright/test' );
const { CUSTOMER_STATE_PATH } = require( '../../playwright.config' );

[ 'twentytwentyfour', 'storefront' ].forEach( ( theme ) => {
	test.describe( `Feature: Managing Notifications: ${ theme }`, () => {
		let helper;

		test.beforeAll( async ( { baseURL } ) => {
			activateTheme( baseURL, theme );
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
		test.describe( 'Logged in', async () => {
			test.use( { storageState: CUSTOMER_STATE_PATH } );
			test( 'View active notifications, simple product', async () => {
				const { given, when, then } = helper;
				await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();

				await when.iClickTheNotifyMeButton();
				await then.iSeeThatMySignupRequestWasSuccessful();
				await when.iAmOnTheStockNotificationsAccountPage();

				await then.iSeeAnActiveNotificationsTable();
				await then.iSeeANotificationForTheProductIHaveSubscribedTo();
			} );
			test( 'View active notifications, variation', async () => {
				const { given, when, then } = helper;
				await given.iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariations();

				await when.iChooseAVariationThatIsOutOfStock();
				await when.iClickTheNotifyMeButton();
				await then.iSeeThatMySignupRequestWasSuccessful();
				await when.iAmOnTheStockNotificationsAccountPage();

				await then.iSeeAnActiveNotificationsTable();
				await then.iSeeANotificationForTheProductIHaveSubscribedTo();
			} );
			test( 'View active notifications, variation with any', async () => {
				const { given, when, then } = helper;
				await given.iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny();

				await when.iChooseAVariationThatIsOutOfStock();
				await when.iClickTheNotifyMeButton();
				await then.iSeeThatMySignupRequestWasSuccessful();
				await when.iAmOnTheStockNotificationsAccountPage();

				await then.iSeeAnActiveNotificationsTable();
				await then.iSeeANotificationForTheProductIHaveSubscribedTo();
			} );
			test( 'Cancel active notifications', async () => {
				const { given, when, then } = helper;
				await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();

				await when.iClickTheNotifyMeButton();
				await then.iSeeThatMySignupRequestWasSuccessful();
				await when.iAmOnTheStockNotificationsAccountPage();

				await then.iSeeThatICanCancelActiveNotifications();

				await when.iCancelAnActiveNotification();

				await then.iSeeThatTheNotificationWasCancelled();
			} );
		} );

		test( 'View pending notifications that require double opt-in', async () => {
			const { given, when, then } = helper;
			await given.signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp();
			await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();
			await when.iEnterMyEmail();
			await when.iClickTheNotifyMeButton();
			await then.iSeeThatMySignupRequestWasSuccessful();
			await when.iAmOnTheStockNotificationsAccountPage();
			await when.iLogInToMyAccount();

			await then.iSeeAPendingNotificationForTheProductIHaveSubscribedTo();
		} );
		test( 'Cancel pending notifications that require double opt-in', async () => {
			const { given, when, then } = helper;
			await given.signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp();
			await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();
			await when.iEnterMyEmail();
			await when.iClickTheNotifyMeButton();
			await then.iSeeThatMySignupRequestWasSuccessful();
			await when.iAmOnTheStockNotificationsAccountPage();
			await when.iLogInToMyAccount();
			await then.iSeeThatICanCancelPendingNotifications();

			await when.iCancelAPendingNotification();

			await then.iSeeThatThePendingNotificationWasCancelled();
		} );
		test( 'Re-send verification emails', async () => {
			const { given, when, then } = helper;
			await given.signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp();
			await given.iAmViewingThePageOfASimpleProductThatIsOutOfStock();
			await when.iEnterMyEmail();
			await when.iClickTheNotifyMeButton();
			await then.iSeeThatMySignupRequestWasSuccessful();
			await when.iAmOnTheStockNotificationsAccountPage();
			await when.iLogInToMyAccount();

			await then.iSeeThatICanReSendVerificationEmails();
			await when.iReSendAVerificationEmail();

			await then.iSeeThatTheEmailWasResentSuccessfully();
		} );
	} );
} );
