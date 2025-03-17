/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	Warning,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Higher-Order Component that checks if a block is inside an invalid Query Loop context.
 * It renders a warning if the block is inside a Query Loop context and the post type is not a product.
 *
 * @param          WrappedComponent The component to wrap
 * @param {string} blockName        The name of the block to display in the warning message
 * @return {Function} A wrapped component that includes query loop context validation
 */
export const withInvalidQueryLoopContext = (
	WrappedComponent: React.ComponentType,
	blockName: string
) => {
	return function WithInvalidQueryLoopContextWrapper( props: any ) {
		const { postType } = props.context;
		const { getBlockParentsByBlockName } = useSelect( blockEditorStore );
		const blockParents = useMemo( () => {
			return getBlockParentsByBlockName(
				props.clientId,
				'core/post-template'
			);
		}, [ getBlockParentsByBlockName, props.clientId ] );

		const isInvalid = blockParents.length > 0 && postType !== 'product';

		const blockProps = useBlockProps();
		if ( isInvalid ) {
			return (
				<div { ...blockProps }>
					<Warning>
						{ sprintf(
							/* translators: %s: block name */
							__(
								'The %s block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
								'woocommerce'
							),
							blockName
						) }
					</Warning>
				</div>
			);
		}

		return <WrappedComponent { ...props } />;
	};
};
