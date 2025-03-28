/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';
import { ProductNoticeSkeleton as ProductNoticeSkeletonComponent } from '../patterns/product-notice';
import { CartExpressPaymentsSkeleton as CartExpressPaymentsSkeletonComponent } from '../patterns/cart-express-payments';
import { CartLineItemsSkeleton as CartLineItemsSkeletonComponent } from '../patterns/cart-line-items';
import { CartOrderSummarySkeleton as CartOrderSummarySkeletonComponent } from '../patterns/cart-order-summary';
import { CheckoutExpressPaymentsSkeleton as CheckoutExpressPaymentsSkeletonComponent } from '../patterns/checkout-express-payments';
import { CheckoutContactSkeleton as CheckoutContactSkeletonComponent } from '../patterns/checkout-contact';
import { CheckoutDeliverySkeleton as CheckoutDeliverySkeletonComponent } from '../patterns/checkout-delivery';
import { CheckoutShippingSkeletonPrimary as CheckoutShippingSkeletonPrimaryComponent } from '../patterns/checkout-shipping-primary';
import { CheckoutShippingSkeletonAdditional as CheckoutShippingSkeletonAdditionalComponent } from '../patterns/checkout-shipping-additional';
import { CheckoutPaymentSkeleton as CheckoutPaymentSkeletonComponent } from '../patterns/checkout-payment';
import { CheckoutOrderSummarySkeleton as CheckoutOrderSummarySkeletonComponent } from '../patterns/checkout-order-summary';
import { CheckoutOrderSummaryMobileSkeleton as CheckoutOrderSummaryMobileSkeletonComponent } from '../patterns/checkout-order-summary-mobile';
export default {
	title: 'Base Components/Skeleton/Patterns',
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
					'Pattern skeletons are reusable structures built from base skeletons for common UI patterns.',
			},
		},
	},
} as Meta< SkeletonProps >;

export const ProductNoticeSkeleton: StoryObj = {
	render: () => <ProductNoticeSkeletonComponent />,
	storyName: 'Product Notice skeleton',
	parameters: {
		docs: {
			source: {
				code: '<ProductNoticeSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the product notices.',
			},
		},
	},
};

export const CartLineItemsSkeleton: StoryObj = {
	render: () => <CartLineItemsSkeletonComponent />,
	storyName: 'Cart Line Items skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartLineItemsSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Cart Line Items section on the Cart block.',
			},
		},
	},
};

export const CartOrderSummarySkeleton: StoryObj = {
	render: () => <CartOrderSummarySkeletonComponent />,
	storyName: 'Cart Order Summary skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartOrderSummarySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Cart block.',
			},
		},
	},
};

export const CartExpressPaymentsSkeleton: StoryObj = {
	render: () => <CartExpressPaymentsSkeletonComponent />,
	storyName: 'Cart Express Payments skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartExpressPayments />',
			},
			description: {
				story: 'The skeleton pattern for the Express Payments section on the Cart block.',
			},
		},
	},
};

export const CheckoutExpressPaymentsPrimarySkeleton: StoryObj = {
	render: () => <CheckoutExpressPaymentsSkeletonComponent />,
	storyName: 'Checkout Express Payments skeleton (primary)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutExpressPaymentsSkeleton />',
			},
			description: {
				story: 'The primary skeleton pattern for the Express Payments section on the Checkout block.',
			},
		},
	},
};

export const CheckoutExpressPaymentsSecondarySkeleton: StoryObj< {
	label: string;
} > = {
	render: () => (
		<CheckoutExpressPaymentsSkeletonComponent showLabels={ true } />
	),
	storyName: 'Checkout Express Payments Skeleton (secondary)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutExpressPaymentsSkeleton showLabels={ true } />',
			},
			description: {
				story: 'The secondary skeleton pattern for the Express Payments section on the Checkout block.',
			},
		},
	},
};

export const CheckoutContactSkeleton: StoryObj = {
	render: () => <CheckoutContactSkeletonComponent />,
	storyName: 'Checkout Contact skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutContactSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Contact section on the Checkout block.',
			},
		},
	},
};

export const CheckoutDeliverySkeleton: StoryObj = {
	render: () => <CheckoutDeliverySkeletonComponent />,
	storyName: 'Checkout Delivery skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutDeliverySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Delivery section on the Checkout block.',
			},
		},
	},
};

export const CheckoutShippingSkeletonPrimary: StoryObj = {
	render: () => <CheckoutShippingSkeletonPrimaryComponent />,
	storyName: 'Checkout Shipping skeleton (primary)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutShippingSkeleton />',
			},
			description: {
				story: 'The primary skeleton pattern for the Shipping section on the Checkout block.',
			},
		},
	},
};

export const CheckoutShippingSkeletonAdditional: StoryObj = {
	render: () => <CheckoutShippingSkeletonAdditionalComponent />,
	storyName: 'Checkout Shipping skeleton (additional)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutShippingSkeletonAdditional />',
			},
			description: {
				story: 'The additional skeleton pattern for the Shipping section on the Checkout block.',
			},
		},
	},
};

export const CheckoutPaymentSkeleton: StoryObj = {
	render: () => <CheckoutPaymentSkeletonComponent />,
	storyName: 'Checkout Payment skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutPaymentSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Payment section on the Checkout block.',
			},
		},
	},
};

export const CheckoutOrderSummarySkeleton: StoryObj = {
	render: () => <CheckoutOrderSummarySkeletonComponent />,
	storyName: 'Checkout Order Summary skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutOrderSummarySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Checkout block.',
			},
		},
	},
};

export const CheckoutOrderSummaryMobileSkeleton: StoryObj = {
	render: () => <CheckoutOrderSummaryMobileSkeletonComponent />,
	storyName: 'Checkout Order Summary Mobile skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutOrderSummaryMobileSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Checkout block on mobile.',
			},
		},
	},
};
