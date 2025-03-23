/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useCollection } from '@woocommerce/base-context/hooks';
import { useQueryLoopProductContextValidation } from '@woocommerce/base-hooks';
/**
 * Internal dependencies
 */
import { ProductAttributesEditProps } from './types';

function Placeholder() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<p>
				{ __(
					'This block displays product attributes including dimensions and weight. When viewing a product page, the attributes will automatically appear here.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}

const Edit = ( {
	context: { postId, postType },
	clientId,
}: ProductAttributesEditProps ) => {
	const blockProps = useBlockProps();
	const isSpecificProductContext = postId && postType;
	const shouldFetchProduct = !! postId;

	const { results: product, isLoading } = useCollection< object >( {
		namespace: '/wc/v3',
		resourceName: 'products',
		resourceValues: [ Number( postId ) ],
		shouldSelect: shouldFetchProduct,
	} );

	/**
	 * Validate Query Loop block context
	 */
	const { hasInvalidContext, warningElement } =
		useQueryLoopProductContextValidation( {
			clientId,
			postType,
			blockName: __( 'Product Attributes', 'woocommerce' ),
		} );
	if ( hasInvalidContext ) {
		return warningElement;
	}

	/**
	 * Display loading state
	 */
	if ( isLoading && shouldFetchProduct ) {
		return (
			<div { ...blockProps }>
				<span className="wc-product-attributes__loading">
					{ __( 'Loading…', 'woocommerce' ) }
				</span>
			</div>
		);
	}

	/**
	 * Display no product found message
	 */
	if ( postId && ! product ) {
		return (
			<div { ...blockProps }>
				<p>{ __( 'No product found', 'woocommerce' ) }</p>
			</div>
		);
	}

	return isSpecificProductContext ? (
		/**
		 * Display product attributes
		 */
		<div { ...blockProps }>
			<table className="wc-block-product-attributes">
				<tbody>
					{ /* Display Weight if available */ }
					{ product.weight && (
						<tr className="wc-block-product-attributes-item wc-block-product-attributes-item__weight">
							<th className="wc-block-product-attributes-item__label">
								{ __( 'Weight', 'woocommerce' ) }
							</th>
							<td className="wc-block-product-attributes-item__value">
								{ product.weight }
							</td>
						</tr>
					) }

					{ /* Display Dimensions if available */ }
					{ product.dimensions &&
						Object.values( product.dimensions ).some(
							( dim ) => dim !== ''
						) && (
							<tr className="wc-block-product-attributes-item wc-block-product-attributes-item__dimensions">
								<th className="wc-block-product-attributes-item__label">
									{ __( 'Dimensions', 'woocommerce' ) }
								</th>
								<td className="wc-block-product-attributes-item__value">
									{ `${ product.dimensions.length } × ${ product.dimensions.width } × ${ product.dimensions.height }` }
								</td>
							</tr>
						) }

					{ /* Display Product Attributes */ }
					{ product.attributes?.map( ( attribute ) => (
						<tr
							key={ attribute.id }
							className={ `wc-block-product-attributes-item wc-block-product-attributes-item__${ attribute.name.toLowerCase() }` }
						>
							<th className="wc-block-product-attributes-item__label">
								{ attribute.name }
							</th>
							<td className="wc-block-product-attributes-item__value">
								{ attribute.options.join( ', ' ) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	) : (
		<Placeholder />
	);
};

export default Edit;
