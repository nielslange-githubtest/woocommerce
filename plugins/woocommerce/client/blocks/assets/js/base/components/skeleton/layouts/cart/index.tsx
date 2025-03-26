/**
 * Internal dependencies
 */
import { CartExpressPaymentsSkeleton as CartExpressPaymentsSkeletonComponent } from '../../patterns/cart-express-payments';
import { CartLineItemsSkeleton as CartLineItemsSkeletonComponent } from '../../patterns/cart-line-items';
import { CartOrderSummarySkeleton as CartOrderSummarySkeletonComponent } from '../../patterns/cart-order-summary';
import '../../../sidebar-layout/style.scss';
import './style.scss';

export const CartSkeleton = () => {
	return (
		<div className="wc-block-components-sidebar-layout">
			<div className="wc-block-components-main">
				<CartLineItemsSkeletonComponent />
			</div>
			<div className="wc-block-components-sidebar">
				<CartOrderSummarySkeletonComponent />
				<CartExpressPaymentsSkeletonComponent />
			</div>
		</div>
	);
};
