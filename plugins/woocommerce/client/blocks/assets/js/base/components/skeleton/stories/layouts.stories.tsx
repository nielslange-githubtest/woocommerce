/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';
import { CartSkeleton as CartSkeletonComponent } from '../layouts/cart';
import { CheckoutSkeleton as CheckoutSkeletonComponent } from '../layouts/checkout';

export default {
	title: 'Base Components/Skeleton/Layouts',
	component: Skeleton,
	argTypes: {
		width: { control: 'text' },
		height: { control: 'text' },
		borderRadius: { control: 'text' },
		className: { control: 'text' },
		tag: {
			control: { type: 'select' },
			options: [ 'div' ],
		},
	},
	parameters: {
		docs: {
			description: {
				component:
					'Layout skeletons compose pattern skeletons into full-page or complex layouts.',
			},
		},
	},
} as Meta< SkeletonProps >;

export const CartSkeleton: StoryObj = {
	render: () => <CartSkeletonComponent />,
	storyName: 'Cart skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartSkeleton />',
			},
			description: {
				story: 'The skeleton for the Cart block.',
			},
		},
	},
};

export const CheckoutSkeleton: StoryObj = {
	render: () => <CheckoutSkeletonComponent />,
	storyName: 'Checkout skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutSkeleton />',
			},
			description: {
				story: 'The skeleton for the Checkout block.',
			},
		},
	},
};
