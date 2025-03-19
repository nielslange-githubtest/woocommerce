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
import { BlockEditProps } from '@wordpress/blocks';

type EditComponentProps = BlockEditProps< Record< string, never > > & {
	context: {
		postType: string;
	};
};

/**
 * Higher-Order Component that checks if a block is inside an invalid Query Loop context.
 * It renders a warning if the block is inside a Query Loop context and the post type is not a product.
 *
 * @param          WrappedComponent The component to wrap
 * @param {string} blockName        The name of the block to display in the warning message
 * @return {Function} A wrapped component that includes query loop context validation
 */
export const withQueryLoopProductContextValidation = (
	WrappedComponent: React.ComponentType< EditComponentProps >,
	blockName: string
) => {
	return function WithQueryLoopValidation( props: EditComponentProps ) {
		const { postType } = props.context;
		const isInvalidQueryLoopContext = useSelect(
			( select ) => {
				const queryLoopAncestors = select(
					blockEditorStore
				).getBlockParentsByBlockName(
					props.clientId,
					'core/post-template'
				);
				return queryLoopAncestors.length > 0 && postType !== 'product';
			},
			[ props.clientId, postType ]
		);

		const blockProps = useBlockProps();
		if ( isInvalidQueryLoopContext ) {
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
