/**
 * Internal dependencies
 */
import { Skeleton } from '../../';

export const ProductNoticeSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton">
			<Skeleton />
			<Skeleton />
			<Skeleton width="80%" />
		</div>
	);
};
