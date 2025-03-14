/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '~/settings-payments/onboarding/context/OnboardingContext';
import { WooPaymentsProviderOnboardingStep } from '~/settings-payments/onboarding/types';

/**
 * Step Components
 */
export const WelcomeStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<div>
			Welcome Step Content
			<button onClick={ () => navigateToNextStep() }>
				Next (Front-end only)
			</button>
			<button onClick={ () => refreshOnboardingSteps() }>
				Refresh redux store
			</button>
		</div>
	);
};

export const JetpackStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<div>
			Jetpack Step Content{ ' ' }
			<button onClick={ () => navigateToNextStep() }>
				Next (Front-end only)
			</button>
			<button onClick={ () => refreshOnboardingSteps() }>
				Refresh redux store
			</button>
		</div>
	);
};

export const OtherStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<div>
			Other Step Content
			<button onClick={ () => navigateToNextStep() }>
				Next (Front-end only)
			</button>
			<button onClick={ () => refreshOnboardingSteps() }>
				Refresh redux store
			</button>
		</div>
	);
};

export const FrontendStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<div>
			Frontend Step Content
			<button onClick={ () => navigateToNextStep() }>
				Next (Front-end only)
			</button>
			<button onClick={ () => refreshOnboardingSteps() }>
				Refresh redux store
			</button>
		</div>
	);
};

export const steps: WooPaymentsProviderOnboardingStep[] = [
	{
		id: 'welcome',
		order: 1,
		type: 'backend',
		label: 'Welcome to WooPayments',
		content: <WelcomeStep />,
	},
	{
		id: 'jetpack',
		order: 2,
		type: 'backend',
		label: 'Connect with Jetpack',
		content: <JetpackStep />,
	},
	{
		id: 'congratulations',
		order: 3,
		type: 'frontend',
		label: 'Congratulations',
		path: '/woopayments/onboarding/congratulations',
		dependencies: [ 'jetpack', 'welcome' ],
		content: <FrontendStep />,
	},
	{
		id: 'final',
		order: 4,
		type: 'backend',
		label: 'Payment methods',
		content: <OtherStep />,
	},
];
