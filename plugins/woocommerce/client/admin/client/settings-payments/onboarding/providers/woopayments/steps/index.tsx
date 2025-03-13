/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '~/settings-payments/onboarding/context/OnboardingContext';

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
/**
 * Frontend-only steps that are not part of the backend steps data
 */
export const frontEndOnlySteps = [
	{
		id: 'congratulations',
		label: 'Congratulations',
		path: '/woopayments/onboarding/congratulations',
		status: 'incomplete' as 'incomplete' | 'completed',
		dependencies: [ 'final' ],
	},
];

/**
 * Map step keys to their components
 */
export const getStepContent = ( stepId: string ): React.ReactNode => {
	switch ( stepId ) {
		case 'welcome':
			return <WelcomeStep />;
		case 'jetpack':
			return <JetpackStep />;
		case 'final':
			return <OtherStep />;
		case 'frontend':
			return <FrontendStep />;
		default:
			return null;
	}
};

export const getStepOrder = ( stepId: string ): number => {
	switch ( stepId ) {
		case 'welcome':
			return 1;
		case 'jetpack':
			return 2;
		case 'final':
			return 3;
		case 'frontend':
			return 4;
		default:
			return 99;
	}
};
