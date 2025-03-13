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
	return <div>Frontend Step Content</div>;
};

/**
 * Frontend-only steps that are not part of the backend steps data
 */
export const frontEndOnlySteps = [
	{
		key: 'congratulations',
		title: 'Congratulations',
		path: '/onboarding/congratulations',
		description: 'You have completed the onboarding process.',
		order: 10,
		status: 'incomplete' as 'incomplete' | 'completed',
	},
];

/**
 * Map step keys to their components
 */
export const getStepContent = ( stepKey: string ): React.ReactNode => {
	switch ( stepKey ) {
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
