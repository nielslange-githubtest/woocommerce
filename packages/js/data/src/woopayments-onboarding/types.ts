export interface StepContent {
	key: string;
	title: string;
	description: string;
	completed: boolean;
	order: number;
}

export interface OnboardingState {
	steps: StepContent[];
	currentStep: string | null;
	isFetching: boolean;
	errors: Record< string, unknown >;
}

export type OnboardingStepsResponse = {
	steps: StepContent[];
};
