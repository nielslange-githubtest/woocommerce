/**
 * Internal dependencies
 */
import { StepContent } from './types';

export function getOnboardingStepsRequest() {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_STEPS_REQUEST' as const,
	};
}

export function getOnboardingStepsSuccess( steps: StepContent[] ) {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_STEPS_SUCCESS' as const,
		steps,
	};
}

export function getOnboardingStepsError( error: unknown ) {
	return {
		type: 'GET_WOOPAYMENTS_ONBOARDING_STEPS_ERROR' as const,
		error,
	};
}

export type Action =
	| ReturnType< typeof getOnboardingStepsRequest >
	| ReturnType< typeof getOnboardingStepsSuccess >
	| ReturnType< typeof getOnboardingStepsError >;
