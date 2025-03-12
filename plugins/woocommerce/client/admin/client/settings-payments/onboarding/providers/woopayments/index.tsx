/**
 * External dependencies
 */
import React from 'react';
import { useLocation } from 'react-router-dom';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
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

// Step components
const WelcomeStep = () => {
	return <div>Welcome Step Content</div>;
};
const JetpackStep = () => {
	return <div>Jetpack Step Content</div>;
};
const OtherStep = () => {
	return <div>Other Step Content</div>;
};

const getStepContentFromStepKey = ( stepKey: string ) => {
	switch ( stepKey ) {
		case 'welcome':
			return <WelcomeStep />;
		case 'jetpack':
			return <JetpackStep />;
		case 'final':
			return <OtherStep />;
		default:
			return null;
	}
};

const WooPaymentsProvider = () => {
	const { steps, isLoading } = useOnboardingContext();

	// If still loading, show a loading indicator
	if ( isLoading ) {
		return (
			<div className="settings-payments-onboarding-modal__loading">
				Loading...
			</div>
		);
	}

	// If we have steps, render the Stepper
	if ( steps && steps.length > 0 ) {
		const stepsMapped = steps.map( ( step ) => ( {
			...step,
			content: getStepContentFromStepKey( step.key ),
		} ) );

		const currentStep = stepsMapped
			.sort( ( a, b ) => a.order - b.order )
			.find( ( step ) => step.status === 'incomplete' );

		return (
			<div className="settings-payments-onboarding-modal__wrapper">
				<Stepper
					title={ __( 'Set up WooPayments', 'woocommerce' ) }
					steps={ stepsMapped }
					active={ currentStep?.key ?? '' }
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
