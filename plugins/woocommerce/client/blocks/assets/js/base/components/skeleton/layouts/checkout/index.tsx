/**
 * Internal dependencies
 */
import { CheckoutExpressPaymentsSkeleton } from '../../patterns/checkout-express-payments';
import { CheckoutOrderSummarySkeleton as CheckoutOrderSummarySkeletonComponent } from '../../patterns/checkout-order-summary';
import '../../../sidebar-layout/style.scss';
import './style.scss';
import { CheckoutDeliverySkeleton } from '../../patterns/checkout-delivery';
import { CheckoutContactSkeleton } from '../../patterns/checkout-contact';
import { CheckoutPaymentSkeleton } from '../../patterns/checkout-payment';
import { CheckoutShippingSkeletonPrimary } from '../../patterns/checkout-shipping-primary';

export const CheckoutSkeleton = () => {
	return (
		<div className="wc-block-components-sidebar-layout">
			<div className="wc-block-components-main">
				<CheckoutExpressPaymentsSkeleton />
				<CheckoutContactSkeleton />
				<CheckoutDeliverySkeleton />
				<CheckoutPaymentSkeleton />
				<CheckoutShippingSkeletonPrimary />
			</div>
			<div className="wc-block-components-sidebar">
				<CheckoutOrderSummarySkeletonComponent />
			</div>
		</div>
	);
};
