export interface StepContent {
	id: string;
	label: string;
	path: string;
	order: number;
	status: 'completed' | 'incomplete';
	dependencies: string[];
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
