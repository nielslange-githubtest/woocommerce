/**
 * Internal dependencies
 */
import { Action } from './actions';
import { OnboardingState } from './types';

const initialState: OnboardingState = {
	steps: [],
	isFetching: false,
	errors: {},
};

const reducer = ( state = initialState, action: Action ): OnboardingState => {
	switch ( action.type ) {
		case 'GET_WOOPAYMENTS_ONBOARDING_STEPS_REQUEST':
			return {
				...state,
				isFetching: true,
			};
		case 'GET_WOOPAYMENTS_ONBOARDING_STEPS_SUCCESS':
			return {
				...state,
				steps: action.steps,
				isFetching: false,
			};
		case 'GET_WOOPAYMENTS_ONBOARDING_STEPS_ERROR':
			return {
				...state,
				errors: {
					...state.errors,
					getOnboardingSteps: action.error,
				},
				isFetching: false,
			};
		default:
			return state;
	}
};

export default reducer;
