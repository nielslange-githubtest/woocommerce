/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useCallback,
	useState,
	useEffect,
} from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { woopaymentsOnboardingStore } from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { steps as woopaymentsSteps } from '../providers/woopayments/steps';
import { WooPaymentsProviderOnboardingStep } from '../types';

/**
 * Internal dependencies
 */

interface OnboardingStepsResponse {
	steps: Array< {
		id: string;
		label: string;
		path: string;
		order: number;
		status: 'completed' | 'incomplete';
		dependencies: string[];
	} >;
}

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

	// Use React state to manage steps and loading state
	const [ stateStoreSteps, setStateStoreSteps ] = useState<
		OnboardingStepsResponse[ 'steps' ]
	>( [] );
	const [ isLocalLoading, setIsLocalLoading ] = useState( true );

	// Initial data fetch from store
	const { storeSteps, isStoreLoading } = useSelect(
		( select ) => ( {
			storeSteps: select(
				woopaymentsOnboardingStore
			).getOnboardingSteps(),
			isStoreLoading: select(
				woopaymentsOnboardingStore
			).isOnboardingStepsRequestPending(),
		} ),
		[]
	);

	// Update local state when store data changes
	useEffect( () => {
		if ( ! isStoreLoading && storeSteps.length > 0 ) {
			setStateStoreSteps( storeSteps );
			setIsLocalLoading( false );
		}
	}, [ storeSteps, isStoreLoading ] );

	const [ allSteps, setAllSteps ] = useState<
		WooPaymentsProviderOnboardingStep[]
	>( [] );

	// Helper function to get step by key
	const getStepByKey = useCallback(
		( stepKey: string ) => {
			return allSteps.find( ( step ) => step.id === stepKey );
		},
		[ allSteps ]
	);

	// Helper function to check if all dependencies of a step are completed
	const areStepDependenciesCompleted = useCallback(
		(
			step: WooPaymentsProviderOnboardingStep,
			steps: WooPaymentsProviderOnboardingStep[]
		) => {
			if ( ! step.dependencies || step.dependencies.length === 0 ) {
				return true;
			}

			return step.dependencies.every( ( dependencyId ) => {
				const dependencyStep = steps.find(
					( s ) => s.id === dependencyId
				);
				return dependencyStep?.status === 'completed';
			} );
		},
		[]
	);

	// Update all steps when stateStoreSteps changes
	useEffect( () => {
		const mapWooPaymentsSteps = woopaymentsSteps.map( ( step ) => {
			// If step type is backend, add the status, path and dependencies from the store
			if ( step.type === 'backend' ) {
				const backendStep = stateStoreSteps.find(
					( s ) => s.id === step.id
				);

				return Object.assign( {}, step, {
					status: backendStep?.status || 'incomplete',
					dependencies: backendStep?.dependencies || [],
					path: backendStep?.path,
					// Maybe actions too
				} );
			}

			// For frontend steps, create a base step object first
			return Object.assign( {}, step );
		} );

		// Now determine dependencies status in a second pass to avoid stale data
		const stepsWithDependenciesResolved = mapWooPaymentsSteps.map(
			( step ) => {
				if ( step.type === 'frontend' ) {
					return {
						...step,
						status: areStepDependenciesCompleted(
							step,
							mapWooPaymentsSteps
						)
							? ( 'completed' as const )
							: ( 'incomplete' as const ),
					};
				}
				return step;
			}
		);

		setAllSteps(
			stepsWithDependenciesResolved as WooPaymentsProviderOnboardingStep[]
		);
	}, [ stateStoreSteps, areStepDependenciesCompleted ] );

	// Make UI refresh when plugin is installed.
	const { invalidateResolutionForStoreSelector } = useDispatch(
		woopaymentsOnboardingStore
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

	// Find the first incomplete step with completed dependencies
	const currentStep = allSteps.find(
		( step ) =>
			step.status === 'incomplete' &&
			areStepDependenciesCompleted( step, allSteps )
	);

	const navigateToNextStep = useCallback( () => {
		const currentStepIndex = allSteps.findIndex(
			( step ) => step.id === currentStep?.id
		);
		if ( currentStepIndex !== -1 ) {
			// Mark current step as completed
			if ( currentStep?.status === 'incomplete' ) {
				// Change step completion status in allSteps
				setAllSteps(
					allSteps.map( ( step ) =>
						step.id === currentStep.id
							? { ...step, status: 'completed' as const }
							: step
					)
				);
			}

			const nextStep = allSteps[ currentStepIndex + 1 ];
			if ( nextStep ) {
				navigateToStep( nextStep.id );
			}
		}
	}, [ currentStep, allSteps, navigateToStep ] );

	const refreshOnboardingSteps = useCallback( () => {
		invalidateResolutionForStoreSelector( 'getOnboardingSteps' );
	}, [ invalidateResolutionForStoreSelector ] );

	return (
		<OnboardingContext.Provider
			value={ {
				steps: allSteps,
				isLoading: isLocalLoading,
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
