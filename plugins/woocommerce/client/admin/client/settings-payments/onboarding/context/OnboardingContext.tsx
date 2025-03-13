/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useCallback,
	useMemo,
} from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { woopaymentsOnboardingStore } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	frontEndOnlySteps,
	getStepContent,
	getStepOrder,
} from '../providers/woopayments/steps';
import { WooPaymentsProviderOnboardingStep } from '../types';

/**
 * Internal dependencies
 */

interface OnboardingContextType {
	steps: WooPaymentsProviderOnboardingStep[];
	isLoading: boolean;
	currentStep: WooPaymentsProviderOnboardingStep | undefined;
	navigateToStep: ( stepKey: string ) => void;
	navigateToNextStep: () => void;
	getStepByKey: (
		stepKey: string
	) => WooPaymentsProviderOnboardingStep | undefined;
	refreshOnboardingSteps: () => void;
}

const OnboardingContext = createContext< OnboardingContextType >( {
	steps: [],
	isLoading: true,
	currentStep: undefined,
	navigateToStep: () => undefined,
	navigateToNextStep: () => undefined,
	getStepByKey: () => undefined,
	refreshOnboardingSteps: () => undefined,
} );

export const useOnboardingContext = () => useContext( OnboardingContext );

export const OnboardingProvider: React.FC< { children: React.ReactNode } > = ( {
	children,
} ) => {
	const history = getHistory();

	const { steps, isLoading } = useSelect(
		( select ) => ( {
			steps: select( woopaymentsOnboardingStore ).getOnboardingSteps(),
			isLoading: select(
				woopaymentsOnboardingStore
			).isOnboardingStepsRequestPending(),
		} ),
		[]
	);

	// Make UI refresh when plugin is installed.
	const { invalidateResolutionForStoreSelector, getOnboardingStepsSuccess } =
		useDispatch( woopaymentsOnboardingStore );

	const allSteps = useMemo(
		() => [ ...steps, ...frontEndOnlySteps ],
		[ steps ]
	)
		.map( ( step ) => ( {
			...step,
			content: getStepContent( step.id ),
			order: getStepOrder( step.id ),
		} ) )
		.sort( ( a, b ) => a.order - b.order );

	// Helper function to get step by key
	// useCallback is used to avoid re-rendering the tree each time the component is rendered
	const getStepByKey = useCallback(
		( stepKey: string ) => {
			return allSteps.find( ( step ) => step.id === stepKey );
		},
		[ allSteps ]
	);

	// Navigation helper
	// useCallback is used to avoid re-rendering the tree each time the component is rendered
	const navigateToStep = useCallback(
		( stepKey: string ) => {
			const step = getStepByKey( stepKey );
			if ( step?.path ) {
				const newPath = getNewPath( { path: step.path }, step.path, {
					page: 'wc-settings',
					tab: 'checkout',
				} );
				history.push( newPath );
			}
		},
		[ getStepByKey, history ]
	);

	// Helper function to check if all dependencies of a step are completed
	const areStepDependenciesCompleted = useCallback(
		( step: WooPaymentsProviderOnboardingStep ) => {
			if ( ! step.dependencies || step.dependencies.length === 0 ) {
				return true;
			}

			return step.dependencies.every( ( dependencyId ) => {
				const dependencyStep = getStepByKey( dependencyId );
				return dependencyStep?.status === 'completed';
			} );
		},
		[ getStepByKey ]
	);

	// Find the first incomplete step with completed dependencies
	const currentStep = allSteps.find(
		( step ) =>
			step.status === 'incomplete' && areStepDependenciesCompleted( step )
	);

	const navigateToNextStep = useCallback( () => {
		const currentStepIndex = allSteps.findIndex(
			( step ) => step.id === currentStep?.id
		);
		if ( currentStepIndex !== -1 ) {
			// Mark current step as completed
			if ( currentStep?.status === 'incomplete' ) {
				// Is this a BE step?
				const isBackendStep = steps.find(
					( s ) => s.id === currentStep.id
				);

				if ( isBackendStep ) {
					const updatedSteps = steps.map( ( s ) =>
						s.id === currentStep.id
							? { ...s, status: 'completed' as const }
							: s
					);

					// Update the steps in the store
					getOnboardingStepsSuccess( updatedSteps );
				}
			}

			const nextStep = allSteps[ currentStepIndex + 1 ];
			if ( nextStep ) {
				navigateToStep( nextStep.id );
			}
		}
	}, [
		currentStep,
		steps,
		allSteps,
		navigateToStep,
		getOnboardingStepsSuccess,
	] );

	const refreshOnboardingSteps = useCallback( () => {
		invalidateResolutionForStoreSelector( 'getOnboardingSteps' );
	}, [ invalidateResolutionForStoreSelector ] );

	return (
		<OnboardingContext.Provider
			value={ {
				steps: allSteps,
				isLoading,
				currentStep,
				navigateToStep,
				navigateToNextStep,
				getStepByKey,
				refreshOnboardingSteps,
			} }
		>
			{ children }
		</OnboardingContext.Provider>
	);
};
