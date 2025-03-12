/**
 * External dependencies
 */
import { Modal, Spinner } from '@wordpress/components';
import { Routes, Route, useLocation, Navigate } from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import '../../style.scss';
import Sidebar from '../../components/sidebar';
import Stepper from '../../components/stepper';
import Step from '../../components/step';
import {
	OnboardingModalProps,
	StepContent,
} from '~/settings-payments/onboarding/types';

/**
 * The inner content of the modal with its own routing
 */
function ModalContent( {
	steps,
}: {
	steps: StepContent[];
	topLevelPath: string;
} ) {
	const location = useLocation();
	const history = getHistory();
	const [ currentStep, setCurrentStep ] = useState< string | null >( null );
	const [ isValidating, setIsValidating ] = useState( true );

	// Get current step from pathname and validate previous steps
	const getCurrentStep = async (): Promise< string > => {
		const path = location.pathname;
		// Find the step that matches the current path
		const matchingStep = steps.find( ( s ) => path === s.path );
		const targetStepKey = matchingStep?.key || steps[ 0 ].key;
		const targetStep = steps.find( ( s ) => s.key === targetStepKey );

		if ( ! targetStep ) return steps[ 0 ].key;

		// Get all steps that need to be validated (steps before the current one)
		const stepsToValidate = steps
			.filter( ( s ) => s.order < targetStep.order )
			.sort( ( a, b ) => a.order - b.order );

		// Check completion of previous steps
		for ( const step of stepsToValidate ) {
			if ( step.confirmCompletion ) {
				const isCompleted = await step.confirmCompletion();
				if ( ! isCompleted ) {
					// If a step is not completed, navigate to it
					const stepPath = step.path;
					const newPath = getNewPath( { path: stepPath }, stepPath, {
						page: 'wc-settings',
						tab: 'checkout',
					} );
					history.push( newPath );
					return step.key;
				}
			}
		}

		return targetStepKey;
	};

	const navigateToStep = async ( step: string ) => {
		const stepPath = steps.find( ( s ) => s.key === step )?.path;
		if ( ! stepPath ) return;

		setIsValidating( true );
		// Validate steps before navigating
		const targetStep = steps.find( ( s ) => s.key === step );
		if ( targetStep ) {
			const actualStep = await getCurrentStep();
			if ( actualStep !== step ) {
				// If getCurrentStep returned a different step, it means we were redirected
				setIsValidating( false );
				return;
			}
		}

		const newPath = getNewPath( { path: stepPath }, stepPath, {
			page: 'wc-settings',
			tab: 'checkout',
		} );
		history.push( newPath );
		setIsValidating( false );
	};

	// Update current step when location changes
	useEffect( () => {
		setIsValidating( true );
		getCurrentStep().then( ( step ) => {
			setCurrentStep( step );
			setIsValidating( false );
		} );
	}, [ location.pathname ] );

	const sidebarSteps = steps
		.map( ( { key, label, order } ) => ( {
			key,
			label,
			isActive: key === currentStep,
			isCompleted:
				order <
				( steps.find( ( s ) => s.key === currentStep )?.order || 0 ),
		} ) )
		.sort(
			( a, b ) =>
				( steps.find( ( s ) => s.key === a.key )?.order || 0 ) -
				( steps.find( ( s ) => s.key === b.key )?.order || 0 )
		);

	const renderContent = () => {
		if ( isValidating ) {
			return (
				<div className="settings-payments-onboarding-modal__loading">
					<Spinner />
				</div>
			);
		}

		if ( ! currentStep ) {
			return null;
		}

		return (
			<Stepper active={ currentStep }>
				{ steps.map( ( { key, content, order } ) => (
					<Step
						id={ key }
						key={ key }
						onFinish={ () =>
							navigateToStep(
								steps.find( ( s ) => s.order === order + 1 )
									?.key as string
							)
						}
					>
						{ content }
					</Step>
				) ) }
			</Stepper>
		);
	};

	if ( isValidating ) {
		return (
			<div className="settings-payments-onboarding-modal__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<>
			{ currentStep && <Sidebar steps={ sidebarSteps } /> }
			<div className="settings-payments-onboarding-modal__content">
				{ renderContent() }
			</div>
		</>
	);
}

/**
 * Onboarding modal component
 */
export default function OnboardingModal( {
	isOpen,
	setIsOpen,
	steps,
	topLevelPath,
	children,
}: OnboardingModalProps ): React.ReactNode {
	const location = useLocation();
	const history = getHistory();

	// Check if current path matches any step
	const isValidStepPath = steps.some(
		( step ) => location.pathname === step.path
	);

	// Redirect to appropriate step when modal opens with invalid path
	useEffect( () => {
		if ( ! isOpen ) return;

		if ( ! isValidStepPath ) {
			// Get the last step (highest order)
			const lastStep = [ ...steps ].sort(
				( a, b ) => b.order - a.order
			)[ 0 ];
			const newPath = getNewPath(
				{ path: lastStep.path },
				lastStep.path,
				{
					page: 'wc-settings',
					tab: 'checkout',
				}
			);
			history.push( newPath );
		}
	}, [ isOpen, isValidStepPath, steps ] );

	return (
		<Modal
			className="settings-payments-onboarding-modal"
			__experimentalHideHeader={ true }
			onRequestClose={ () => setIsOpen( false ) }
			isFullScreen={ true }
			shouldCloseOnClickOutside={ false }
		>
			<div className="settings-payments-onboarding-modal__wrapper">
				<Routes>
					{ /* If no step is defined, redirect to first step */ }
					<Route
						path={ `${ topLevelPath }` }
						element={ <Navigate to={ steps[ 0 ].path } replace /> }
					/>
					{ /* Handle all onboarding routes */ }
					<Route
						path={ `${ topLevelPath }/*` }
						element={
							<ModalContent
								steps={ steps }
								topLevelPath={ topLevelPath }
							/>
						}
					/>
				</Routes>
				{ children }
			</div>
		</Modal>
	);
}
