/**
 * External dependencies
 */
import React, { useState, useRef, useEffect } from 'react';

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';
interface AccountData {
	status: string;
}

const TestDriveLoader: React.FunctionComponent< {
	progress: number;
} > = ( { progress } ) => (
	<Loader className="woocommerce-payments-test-account-step__preloader">
		<Loader.Layout className="woocommerce-payments-test-account-step__preloader-layout">
			<Loader.Illustration>
				<img
					src={ `${ WC_ASSET_URL }images/onboarding/test-account-setup.svg` }
					alt="setup"
					style={ { maxWidth: '223px' } }
				/>
			</Loader.Illustration>

			<Loader.Title>
				{ __( 'Finishing payments setup', 'woocommerce' ) }
			</Loader.Title>
			<Loader.ProgressBar progress={ progress ?? 0 } />
			<Loader.Sequence interval={ 0 }>
				{ __(
					"In just a few moments, you'll be ready to test payments on your store.",
					'woocommerce'
				) }
			</Loader.Sequence>
		</Loader.Layout>
	</Loader>
);

const TestAccountStep = ( {
	connectUrl,
	checkUrl,
}: {
	connectUrl: string;
	checkUrl: string;
} ) => {
	const { currentStep, navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	const [ testDriveLoaderProgress, setTestDriveLoaderProgress ] =
		useState( 5 );

	const [ errorMessage, setErrorMessage ] = useState( '' );

	// Create a reference object.
	const loaderProgressRef = useRef( testDriveLoaderProgress );
	loaderProgressRef.current = testDriveLoaderProgress;

	const updateLoaderProgress = ( maxPercent: number, step: number ) => {
		if ( loaderProgressRef.current < maxPercent ) {
			const newProgress = loaderProgressRef.current + step;
			setTestDriveLoaderProgress( newProgress );
		}
	};

	useEffect( () => {
		if ( currentStep?.status === 'not_started' ) {
			// Send a request to the server to start the test account setup.
			apiFetch( {
				url: currentStep?.actions?.init?.href,
				method: 'POST',
			} );

			// Create a polling function to check the status of the test account setup.
			const checkTestAccountStatus = () => {
				// Add progress
				updateLoaderProgress( 100, 6 );

				apiFetch( {
					url: currentStep?.actions?.check?.href,
					method: 'POST',
				} ).then( ( response ) => {
					if (
						( response as { status: string; success: boolean } )
							?.status === 'completed'
					) {
						// Refresh the onboarding steps to get the latest status.
						refreshOnboardingSteps();
					}
				} );
			};

			// Check the status of the test account setup every 2.5 seconds.
			const interval = setInterval( checkTestAccountStatus, 2500 );
			return () => clearInterval( interval );
		}
	}, [
		currentStep?.status,
		currentStep?.actions?.init?.href,
		currentStep?.actions?.check?.href,
		refreshOnboardingSteps,
	] );

	// Use a timer to track the elapsed time for the test drive mode setup.
	let testDriveSetupStartTime: number;
	// The test drive setup will be forced finished after 40 seconds
	// (10 seconds for the initial calls plus 30 for checking the account status in a loop).
	const testDriveSetupMaxDuration = 40;

	// Helper function to calculate the elapsed time in seconds.
	const elapsed = ( time: number ) =>
		Math.round( ( Date.now() - time ) / 1000 );

	const urlParams = new URLSearchParams( window.location.search );

	const determineTrackingSource = () => {
		// If we have a source query param in the current request, use that.
		const urlSource = urlParams.get( 'source' )?.replace( /[^\w-]+/g, '' );
		if ( !! urlSource && 'unknown' !== urlSource ) {
			return urlSource;
		}

		// Next, search for a source in the Connect URL as that is determined server-side and it is reliable.
		if ( connectUrl.includes( 'source=' ) ) {
			const url = new URL( connectUrl );
			const source = url.searchParams.get( 'source' );
			if ( !! source && 'unknown' !== source ) {
				return source;
			}
		}
		// Finally, make some guesses based on the 'from' query param.
		// We generally should not reach this step, but it's a fallback with reliable guesses.
		const urlFrom = urlParams.get( 'from' ) || '';
		let sourceGuess = 'wcpay-connect-page';
		switch ( urlFrom ) {
			case 'WCADMIN_PAYMENT_TASK':
				sourceGuess = 'wcadmin-payment-task';
				break;
			case 'WCADMIN_PAYMENT_SETTINGS':
				sourceGuess = 'wcadmin-settings-page';
				break;
			case 'WCADMIN_PAYMENT_INCENTIVE':
				sourceGuess = 'wcadmin-incentive-page';
				break;
		}

		return sourceGuess;
	};

	const determineTrackingFrom = () => {
		return urlParams.get( 'from' )?.replace( /[^\w-]+/g, '' ) || '';
	};

	const checkAccountStatus = ( extraQueryArgs = {} ) => {
		// Fetch account status from the cache.
		apiFetch( {
			path: checkUrl,
			method: 'GET',
		} ).then( ( account ) => {
			// Simulate the update of the loader progress bar by 4% per check.
			// Limit to a maximum of 10 checks (6% progress per each request starting from 40% = max 10 checks).
			updateLoaderProgress( 100, 6 );

			// If the account status is not a pending one, the progress percentage is above 95,
			// or we've exceeded the timeout, consider our work done and redirect the merchant.
			// Otherwise, schedule another check after a 2.5 seconds wait.
			if (
				( account &&
					( account as AccountData ).status &&
					! ( account as AccountData ).status.includes(
						'pending'
					) ) ||
				loaderProgressRef.current > 95 ||
				elapsed( testDriveSetupStartTime ) > testDriveSetupMaxDuration
			) {
				setTestDriveLoaderProgress( 100 );
				const queryArgs = {
					test_drive: 'true',
					'wcpay-sandbox-success': 'true',
					source: determineTrackingSource(),
					from: 'WCPAY_CONNECT',
					redirect_to_settings_page:
						urlParams.get( 'redirect_to_settings_page' ) || '',
				};

				// Redirect to the Connect URL and let it figure it out where to point the merchant.
				window.location.href = addQueryArgs( connectUrl, {
					...queryArgs,
					...extraQueryArgs,
				} );
			} else {
				// Schedule another check after 2.5 seconds.
				// 2.5 seconds plus 0.5 seconds for the fetch request is 3 seconds.
				// With a maximum of 10 checks, we will wait for 30 seconds before ending the process normally.
				setTimeout( () => checkAccountStatus( extraQueryArgs ), 2500 );
			}
		} );
	};

	let isAccountSetupSessionError = false;
	// Determine if we have the account session error message since we want to customize the UX a little bit.
	if ( errorMessage && errorMessage.includes( 'account setup session' ) ) {
		isAccountSetupSessionError = true;
	}

	const isAccountTestDriveError =
		urlParams.get( 'test_drive_error' ) === 'true';
	if ( ! errorMessage && isAccountTestDriveError ) {
		// If there isn't an error message from elsewhere, but we have a test drive error,
		// show the test drive error message.
		setErrorMessage(
			__(
				'An error occurred while setting up your sandbox account. Please try again!',
				'woocommerce'
			)
		);
	}
	return (
		<div className="woocommerce-payments-test-account-step">
			<WooPaymentsStepHeader onClose={ () => {} } />
			<TestDriveLoader progress={ testDriveLoaderProgress } />
		</div>
	);
};

export default TestAccountStep;
