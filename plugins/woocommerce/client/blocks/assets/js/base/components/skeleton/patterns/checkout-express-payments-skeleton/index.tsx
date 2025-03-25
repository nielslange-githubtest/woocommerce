/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import '../../../../../blocks/cart-checkout-shared/payment-methods/express-payment/style.scss';
import './style.scss';

export const CheckoutExpressPaymentsSkeleton = () => {
	return (
		<div className="wc-block-components-express-payment wc-block-components-express-payment--checkout">
			<div className="wc-block-components-express-payment__title-container">
				<h2 className="wc-block-components-title wc-block-components-express-payment__title">
					<Skeleton />
				</h2>
			</div>
			<div className="wc-block-components-express-payment__content">
				<ul className="wc-block-components-express-payment__event-buttons">
					<li>
						<Skeleton height="2.5em" />
					</li>
					<li>
						<Skeleton height="2.5em" />
					</li>
				</ul>
			</div>
		</div>
	);
};
