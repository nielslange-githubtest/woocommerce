/**
 * External dependencies
 */
import React, { useState, useRef, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';

interface StepCheckResponse {
	status: string;
	success: boolean;
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

const TestAccountStep = () => {
	const { currentStep, refreshOnboardingSteps } = useOnboardingContext();
	const [ testDriveLoaderProgress, setTestDriveLoaderProgress ] =
		useState( 5 );
	const [ errorMessage, setErrorMessage ] = useState< string | undefined >();
	const [ retryCounter, setRetryCounter ] = useState( 0 );

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
			} ).catch( ( response ) => {
				setErrorMessage( response.message );
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
						( response as StepCheckResponse )?.status ===
						'completed'
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

		if ( currentStep?.errors?.[ 0 ] ) {
			setErrorMessage( currentStep.errors[ 0 ] );
		}
	}, [
		currentStep?.status,
		currentStep?.errors,
		currentStep?.actions?.init?.href,
		currentStep?.actions?.check?.href,
		refreshOnboardingSteps,
		retryCounter,
	] );

	return (
		<div className="woocommerce-payments-test-account-step">
			<WooPaymentsStepHeader onClose={ () => {} } />
			{ errorMessage && (
				<Notice
					status="error"
					isDismissible={ false }
					className="woocommerce-payments-test-account-step__error"
				>
					<p className="woocommerce-payments-test-account-step__error-message">
						{ errorMessage }
					</p>
				</Notice>
			) }
			<TestDriveLoader progress={ testDriveLoaderProgress } />
		</div>
	);
};

export default TestAccountStep;
