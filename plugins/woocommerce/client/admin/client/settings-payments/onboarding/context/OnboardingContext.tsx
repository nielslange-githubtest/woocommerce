/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	WooPaymentsOnboardingStepContent,
	woopaymentsOnboardingStore,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */

interface OnboardingContextType {
	steps: WooPaymentsOnboardingStepContent[];
	isLoading: boolean;
	currentStep: WooPaymentsOnboardingStepContent | undefined;
}

const OnboardingContext = createContext< OnboardingContextType >( {
	steps: [],
	isLoading: true,
	currentStep: undefined,
} );

export const useOnboardingContext = () => useContext( OnboardingContext );

export const OnboardingProvider: React.FC< { children: React.ReactNode } > = ( {
	children,
} ) => {
	const { steps, isLoading } = useSelect(
		( select ) => ( {
			steps: select( woopaymentsOnboardingStore ).getOnboardingSteps(),
			isLoading: select(
				woopaymentsOnboardingStore
			).isOnboardingStepsRequestPending(),
		} ),
		[]
	);

	const currentStep = steps
		.sort( ( a, b ) => a.order - b.order )
		.find( ( step ) => step.status === 'incomplete' );

	return (
		<OnboardingContext.Provider value={ { steps, isLoading, currentStep } }>
			{ children }
		</OnboardingContext.Provider>
	);
};
