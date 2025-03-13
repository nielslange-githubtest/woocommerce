/**
 * External dependencies
 */
import React from 'react';
import { useLocation } from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import '../../style.scss';
import Modal from '../../components/modal';
import { WooPaymentsModalProps } from '~/settings-payments/onboarding/types';
import Stepper from '../../components/stepper';
import {
	OnboardingProvider,
	useOnboardingContext,
} from '../../context/OnboardingContext';
import { getStepContent, frontEndOnlySteps } from './steps';

const WooPaymentsProvider = () => {
	const { steps, isLoading, currentStep } = useOnboardingContext();

	// If still loading, show a loading indicator
	if ( isLoading ) {
		return (
			<div className="settings-payments-onboarding-modal__loading">
				<Spinner />
			</div>
		);
	}

	// If we have steps, render the Stepper
	if ( steps && steps.length > 0 ) {
		const completeSteps = [ ...steps, ...frontEndOnlySteps ].sort(
			( a, b ) => a.order - b.order
		);
		const stepsMapped = completeSteps.map( ( step ) => ( {
			...step,
			content: getStepContent( step.key ),
		} ) );

		return (
			<div className="settings-payments-onboarding-modal__wrapper">
				<Stepper
					steps={ stepsMapped }
					active={ currentStep?.key ?? '' }
					includeSidebar
					sidebarTitle={ __( 'Set up WooPayments', 'woocommerce' ) }
				/>
			</div>
		);
	}

	// If no steps are available after loading
	return (
		<div className="settings-payments-onboarding-modal__error">
			No onboarding steps available
		</div>
	);
};
/**
 * Modal component for WooPayments onboarding
 */
export default function WooPaymentsModal( {
	isOpen,
	setIsOpen,
}: WooPaymentsModalProps ): React.ReactNode {
	const location = useLocation();
	const history = getHistory();

	// Open modal when on an onboarding route
	React.useEffect( () => {
		if ( location.pathname.startsWith( '/onboarding' ) && ! isOpen ) {
			setIsOpen( true );
		}
	}, [ location, isOpen, setIsOpen ] );

	// Handle modal close by navigating away from onboarding routes
	const handleClose = () => {
		const newPath = getNewPath( {}, '/wp-admin/admin.php', {
			page: 'wc-settings',
			tab: 'checkout',
		} );
		history.push( newPath );
		setIsOpen( false );
	};

	if ( ! isOpen ) return null;

	return (
		<Modal setIsOpen={ handleClose }>
			<OnboardingProvider>
				<WooPaymentsProvider />
			</OnboardingProvider>
		</Modal>
	);
}
