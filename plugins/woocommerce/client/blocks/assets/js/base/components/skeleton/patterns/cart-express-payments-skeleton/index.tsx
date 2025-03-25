/**
 * Internal dependencies
 */
import { Skeleton } from '../../';

export const CartExpressPaymentsSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton">
			<Skeleton height="2.5em" />
			<Skeleton height="2.5em" />
		</div>
	);
};
