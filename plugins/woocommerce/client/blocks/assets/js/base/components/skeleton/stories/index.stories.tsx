/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';
import { ProductNoticeSkeleton as ProductNoticeSkeletonComponent } from '../patterns/product-notice-skeleton';
import { CartExpressPaymentsSkeleton as CartExpressPaymentsSkeletonComponent } from '../patterns/cart-express-payments-skeleton';
import { CheckoutExpressPaymentsSkeleton as CheckoutExpressPaymentsSkeletonComponent } from '../patterns/checkout-express-payments-skeleton';
export default {
	title: 'Base Components/Skeleton',
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
					'Skeleton is a placeholder component used during loading states.',
			},
		},
	},
} as Meta< SkeletonProps >;

const Template = ( args: SkeletonProps ) => <Skeleton { ...args } />;

export const Default: StoryObj< SkeletonProps > = {
	render: Template,
	args: {},
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with the default args.',
			},
		},
	},
};

export const CustomHeight: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		height: '2.5em',
	},
	storyName: 'Custom height',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom height.',
			},
		},
	},
};

export const CustomWidth: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		width: '50%',
	},
	storyName: 'Custom width',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component with a custom width.',
			},
		},
	},
};

export const NoBorderRadius: StoryObj< SkeletonProps > = {
	render: Template,
	args: {
		borderRadius: '0',
	},
	storyName: 'No border radius',
	parameters: {
		docs: {
			description: {
				story: 'The base skeleton component without a border radius.',
			},
		},
	},
};

export const ProductNoticeSkeleton: StoryObj = {
	render: () => <ProductNoticeSkeletonComponent />,
	parameters: {
		docs: {
			source: {
				code: '<ProductNoticeSkeleton />',
			},
			description: {
				story: 'The Skeleton pattern for the product notices.',
			},
		},
	},
};

export const CartExpressPaymentsSkeleton: StoryObj = {
	render: () => <CartExpressPaymentsSkeletonComponent />,
	parameters: {
		docs: {
			source: {
				code: '<CartExpressPayments />',
			},
			description: {
				story: 'The Skeleton pattern for the Express Payments on the Cart block.',
			},
		},
	},
};

export const CheckoutExpressPaymentsSkeleton: StoryObj = {
	render: () => <CheckoutExpressPaymentsSkeletonComponent />,
	parameters: {
		docs: {
			source: {
				code: '<CheckoutExpressPaymentsSkeleton />',
			},
			description: {
				story: 'The Skeleton pattern for the Express Payments on the Checkout block.',
			},
		},
	},
};
