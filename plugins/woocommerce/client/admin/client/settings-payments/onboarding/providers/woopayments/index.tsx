/**
 * External dependencies
 */
import React from 'react';
import { Route, Routes, useLocation } from 'react-router-dom';
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
		return (
			<Routes>
				<Route
					path="/woopayments/onboarding/*"
					element={
						<div className="settings-payments-onboarding-modal__wrapper">
							<Stepper
								steps={ steps }
								active={ currentStep?.id ?? '' }
								includeSidebar
								sidebarTitle={ __(
									'Set up WooPayments',
									'woocommerce'
								) }
							/>
						</div>
					}
				/>
			</Routes>
		);
	}

	return null;
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
	const wooPaymentsOnboardingPath = '/woopayments/onboarding';

	// Open modal when on an onboarding route
	React.useEffect( () => {
		if (
			location.pathname.startsWith( wooPaymentsOnboardingPath ) &&
			! isOpen
		) {
			setIsOpen( true );
		}
	}, [ location, isOpen, setIsOpen ] );

	// If the modal is open, without an onboarding route, add an onboarding route
	React.useEffect( () => {
		if (
			isOpen &&
			! location.pathname.startsWith( wooPaymentsOnboardingPath )
		) {
			const newPath = getNewPath(
				{ path: wooPaymentsOnboardingPath },
				wooPaymentsOnboardingPath,
				{
					page: 'wc-settings',
					tab: 'checkout',
				}
			);
			history.push( newPath );
		}
	}, [ isOpen, location.pathname, history ] );

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
