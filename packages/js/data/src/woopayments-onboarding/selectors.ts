/**
 * Internal dependencies
 */
import { OnboardingState, StepContent } from './types';
import { WPDataSelector, WPDataSelectors } from '../types';

export const getOnboardingSteps = ( state: OnboardingState ): StepContent[] =>
	state.steps;

export const isOnboardingStepsRequestPending = (
	state: OnboardingState
): boolean => state.isFetching;

export const getOnboardingStepsError = ( state: OnboardingState ): unknown =>
	state.errors.getOnboardingSteps;

export type WooPaymentsOnboardingSelectors = {
	getOnboardingSteps: WPDataSelector< typeof getOnboardingSteps >;
	isOnboardingStepsRequestPending: WPDataSelector<
		typeof isOnboardingStepsRequestPending
	>;
	getOnboardingStepsError: WPDataSelector< typeof getOnboardingStepsError >;
} & WPDataSelectors;
