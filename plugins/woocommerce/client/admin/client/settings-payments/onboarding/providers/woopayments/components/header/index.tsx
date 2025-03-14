/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';

interface WooPaymentsStepHeaderProps {
	onClose: () => void;
}

/**
 * RecommendedMethods component for WooPayments onboarding
 */
export default function WooPaymentsStepHeader(
	{ onClose }: WooPaymentsStepHeaderProps
): React.ReactNode {
	return (
		<div className="settings-payments-onboarding-modal__header">
			<img
				src={ `${ WC_ASSET_URL }images/woo-logo.svg` }
				alt="Woo Logo"
				className="settings-payments-onboarding-modal__header--logo"
			/>
			<Button
				className="settings-payments-onboarding-modal__header--close"
				onClick={ onClose }
			>
				<Icon icon={ close } />
			</Button>
		</div>
	);
}
