/**
 * External dependencies
 */
import React, { ReactNode } from 'react';
import { WooPaymentsOnboardingStepContent } from '@woocommerce/data';

/**
 * Props for the Onboarding Sidebar component.
 */
export interface OnboardingSidebarProps {
	steps: {
		key: string;
		label: string;
		isCompleted?: boolean;
		isActive?: boolean;
		content?: React.ReactNode;
	}[];
}

/**
 * Props for the Onboarding Modal component.
 */
export interface OnboardingModalProps {
	setIsOpen: ( isOpen: boolean ) => void;
	children?: ReactNode;
}

/**
 * Props for the WooPayments onboarding modal.
 */
export interface WooPaymentsModalProps {
	isOpen: boolean;
	setIsOpen: ( isOpen: boolean ) => void;
	currentStep?: number;
}

/**
 * Sidebar navigation item props
 */
export interface SidebarItemProps {
	label: string;
	isCompleted?: boolean;
	isActive?: boolean;
}

/**
 * Props for the Stepper component.
 */
export interface StepperProps {
	active: string;
	steps: WooPaymentsProviderOnboardingStep[];
	title: string;
}

/**
 * WooPayments provider onboarding step that extends the base WooPaymentsOnboardingStepContent
 * with additional fields specific to the provider implementation.
 */
export interface WooPaymentsProviderOnboardingStep
	extends WooPaymentsOnboardingStepContent {
	content: ReactNode;
}

/**
 * Props for the Step component.
 */
export interface StepProps {
	id: string;
	children: ReactNode;
	onFinish?: () => void;
}

/**
 * Props for the Step content component.
 */
export interface StepContentProps {
	onFinish?: () => void;
}

/**
 * Props for the Step content passed to the modal.
 */
export interface StepContent {
	key: string;
	label: string;
	path: string;
	order: number;
	content: ReactNode | ( ( props: StepContentProps ) => ReactNode );
	confirmCompletion?: () => Promise< boolean >;
}
