/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';
import { setComingSoon } from '../utils/coming-soon';
import { skipOnboardingWizard } from '../utils/onboarding';
import { WC_API_PATH, WP_API_PATH } from '../utils/api-client';

setup( 'configure HPOS', async ( { restApi } ) => {
	const { DISABLE_HPOS } = process.env;
	console.log( `DISABLE_HPOS: ${ DISABLE_HPOS }` );

	if ( DISABLE_HPOS ) {
		const hposSettingRetries = 5;
		const value = DISABLE_HPOS === '1' ? 'no' : 'yes';
		let hposConfigured = false;

		for ( let i = 0; i < hposSettingRetries; i++ ) {
			try {
				console.log(
					`Trying to switch ${
						value === 'yes' ? 'on' : 'off'
					} HPOS...`
				);
				const response = await restApi.post(
					`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`,
					{ value }
				);
				if ( response.data.value === value ) {
					console.log(
						`HPOS Switched ${
							value === 'yes' ? 'on' : 'off'
						} successfully`
					);
					hposConfigured = true;
					break;
				}
			} catch ( e ) {
				console.log(
					`HPOS setup failed. Retrying... ${ i }/${ hposSettingRetries }`
				);
				console.log( e );
			}
		}

		if ( ! hposConfigured ) {
			console.error(
				'Cannot proceed e2e test, HPOS configuration failed. Please check if the correct DISABLE_HPOS value was used and the test site has been setup correctly.'
			);
			process.exit( 1 );
		}
	}

	const response = await restApi.get(
		`${ WC_API_PATH }/settings/advanced/woocommerce_custom_orders_table_enabled`
	);
	const dataValue = response.data.value;
	const enabledOption = response.data.options[ dataValue ];
	console.log(
		`HPOS configuration (woocommerce_custom_orders_table_enabled): ${ dataValue } - ${ enabledOption }`
	);
} );

setup( 'disable coming soon', async ( { baseURL } ) => {
	await setComingSoon( { baseURL, enabled: 'no' } );
} );

setup( 'disable onboarding wizard', async () => {
	await skipOnboardingWizard();
} );

setup( 'determine if multisite', async ( { restApi } ) => {
	const response = await restApi.get( `${ WC_API_PATH }/system_status` );
	const { environment } = response.data;

	if ( environment.wp_multisite === false ) {
		delete process.env.IS_MULTISITE;
	} else {
		process.env.IS_MULTISITE = environment.wp_multisite;
		console.log( `IS_MULTISITE: ${ process.env.IS_MULTISITE }` );
	}
} );

setup( 'general settings', async ( { restApi } ) => {
	await restApi.post( `${ WC_API_PATH }/settings/general/batch`, {
		update: [
			{ id: 'woocommerce_allowed_countries', value: 'all' },
			{ id: 'woocommerce_currency', value: 'USD' },
			{ id: 'woocommerce_price_thousand_sep', value: ',' },
			{ id: 'woocommerce_price_decimal_sep', value: '.' },
			{ id: 'woocommerce_price_num_decimals', value: '2' },
			{ id: 'woocommerce_store_address', value: 'addr 1' },
			{ id: 'woocommerce_store_city', value: 'San Francisco' },
			{ id: 'woocommerce_default_country', value: 'US:CA' },
			{ id: 'woocommerce_store_postcode', value: '94107' },
		],
	} );
} );

setup( 'create BIS pages', async ( { restApi } ) => {
	console.log( 'Creating BIS pages...' );

	// List all pages
	const response_list = await restApi.get(
		`${ WP_API_PATH }/pages?slug=confirmation-email,verification-email,notification-email`,
		{
			data: {
				_fields: [ 'id', 'slug' ],
			},
			failOnStatusCode: true,
		}
	);

	const list = response_list.data;

	const pages = [
		{
			slug: 'confirmation-email',
			title: 'Confirmation email',
			content: '[bis_confirmation_received_email]',
		},
		{
			slug: 'verification-email',
			title: 'Verification email',
			content: '[bis_verify_received_email]',
		},
		{
			slug: 'notification-email',
			title: 'Notification email',
			content: '[bis_notification_received_email]',
		},
	];

	for ( const page of pages ) {
		const existingPage = list.find( ( p ) => p.slug === page.slug );
		if ( existingPage ) {
			console.log( `Updating ${ page.title } page:`, existingPage.id );
			await restApi.put( `${ WP_API_PATH }/pages/${ existingPage.id }`, {
				content: {
					raw: page.content,
				},
				failOnStatusCode: true,
			} );
		} else {
			console.log( `Creating ${ page.title } page...` );
			const response = await restApi.post( `${ WP_API_PATH }/pages`, {
				title: {
					raw: page.title,
				},
				slug: page.slug,
				content: {
					raw: page.content,
				},
				status: 'publish',
			} );
			const newPage = response.data;
			if (
				newPage.title.rendered === page.title &&
				newPage.slug === page.slug
			) {
				console.log( `Created ${ page.title } page:`, newPage.id );
			} else {
				console.error( `Failed to create ${ page.title } page` );
			}
		}
	}
} );
