/**
 * Internal dependencies
 */
import WooPaymentsStepHeader from '../../components/header';
import { useOnboardingContext } from '../../data/onboarding-context';

export default function TestAccountStep() {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				Other Step Content
				<button onClick={ () => navigateToNextStep() }>
					Next (Front-end only)
				</button>
				<button onClick={ () => refreshOnboardingSteps() }>
					Refresh redux store
				</button>
			</div>
		</>
	);
}
