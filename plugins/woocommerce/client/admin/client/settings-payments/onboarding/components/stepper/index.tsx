/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import {
	SidebarItemProps,
	WooPaymentsProviderOnboardingStep,
} from '~/settings-payments/onboarding/types';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { useOnboardingContext } from '../../context/OnboardingContext';

/**
 * Sidebar navigation item component
 */
const SidebarItem = ( {
	label,
	isCompleted,
	isActive,
}: SidebarItemProps ): React.ReactNode => {
	return (
		<div
			className={ clsx(
				'settings-payments-onboarding-modal__sidebar--list-item',
				{
					'is-active': isActive,
					'is-completed': isCompleted,
				}
			) }
		>
			<span className="settings-payments-onboarding-modal__sidebar--list-item-icon">
				{ isCompleted ? (
					<img
						src={
							WC_ASSET_URL +
							'images/onboarding/icons/complete.svg'
						}
						alt={ __( 'Step completed', 'woocommerce' ) }
					/>
				) : (
					<img
						src={
							WC_ASSET_URL + 'images/onboarding/icons/pending.svg'
						}
						alt={ __( 'Step active', 'woocommerce' ) }
					/>
				) }
			</span>
			<span className="settings-payments-onboarding-modal__sidebar--list-item-label">
				{ label }
			</span>
		</div>
	);
};

/**
 * Stepper component that renders only the active step from its children
 */
export default function Stepper( {
	active,
	steps,
	includeSidebar = false,
	sidebarTitle,
}: {
	/**
	 * The active step key
	 */
	active: string;
	/**
	 * The steps to render
	 */
	steps: WooPaymentsProviderOnboardingStep[];
	/**
	 * The title of the sidebar
	 */
	sidebarTitle?: string;
	/**
	 * Whether to include the sidebar
	 */
	includeSidebar?: boolean;
} ): React.ReactNode {
	const { navigateToStep } = useOnboardingContext();
	const location = useLocation();
	// Find the active step component
	const activeStep = steps.find( ( step ) => step.id === active );

	// Force navigation to current step only if the URL doesn't already match
	useEffect( () => {
		if ( activeStep && location.pathname !== activeStep.path ) {
			navigateToStep( activeStep.id );
		}
	}, [ activeStep, navigateToStep, location.pathname ] );

	if ( ! activeStep ) return null;

	// Only render the active step
	return (
		<>
			{ includeSidebar && (
				<div className="settings-payments-onboarding-modal__sidebar">
					<div className="settings-payments-onboarding-modal__sidebar--header">
						<h2 className="settings-payments-onboarding-modal__sidebar--header-title">
							{ sidebarTitle }
						</h2>
						<div className="settings-payments-onboarding-modal__sidebar--header-steps">
							{ /* translators: %1$s: current step number, %2$s: total number of steps */ }
							{ sprintf(
								/* translators: %1$s: current step number, %2$s: total number of steps */
								__( 'Step %1$s of %2$s', 'woocommerce' ),
								steps.findIndex(
									( step ) => step.id === active
								) + 1,
								steps.length
							) }
						</div>
					</div>
					<div className="settings-payments-onboarding-modal__sidebar--list">
						{ steps.map( ( step ) => (
							<SidebarItem
								key={ step.id }
								label={ step.label }
								isCompleted={ step.status === 'completed' }
								isActive={ step.id === active }
							/>
						) ) }
					</div>
				</div>
			) }
			<div className="settings-payments-onboarding-modal__content">
				<div
					className="settings-payments-onboarding-modal__step"
					id={ activeStep.id }
				>
					{ activeStep.content }
				</div>
			</div>
		</>
	);
}
