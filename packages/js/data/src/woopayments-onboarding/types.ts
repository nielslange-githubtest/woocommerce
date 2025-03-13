export interface StepContent {
	key: string;
	title: string;
	path: string;
	description: string;
	order: number;
	status: 'completed' | 'incomplete';
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
