/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useCollection } from '@woocommerce/base-context/hooks';

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
}: ProductAttributesEditProps ) => {
	const blockProps = useBlockProps();
	const isSpecificProductContext = postId && postType;

	const { results: product, isLoading } = useCollection< object >( {
		namespace: '/wc/v3',
		resourceName: 'products',
		resourceValues: [ Number( postId ) ],
	} );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<span className="wc-product-attributes__loading">
					{ __( 'Loading…', 'woocommerce' ) }
				</span>
			</div>
		);
	}

	if ( postId && ! product ) {
		return (
			<div { ...blockProps }>
				<p>{ __( 'No product found', 'woocommerce' ) }</p>
			</div>
		);
	}

	return isSpecificProductContext ? (
		<div { ...blockProps }>
			<table className="woocommerce-product-attributes shop_attributes">
				<tbody>
					{ /* Display Weight if available */ }
					{ product.weight && (
						<tr className="woocommerce-product-attributes-item woocommerce-product-attributes-item--weight">
							<th className="woocommerce-product-attributes-item__label">
								{ __( 'Weight', 'woocommerce' ) }
							</th>
							<td className="woocommerce-product-attributes-item__value">
								{ product.weight }
							</td>
						</tr>
					) }

					{ /* Display Dimensions if available */ }
					{ product.dimensions &&
						Object.values( product.dimensions ).some(
							( dim ) => dim !== ''
						) && (
							<tr className="woocommerce-product-attributes-item woocommerce-product-attributes-item--dimensions">
								<th className="woocommerce-product-attributes-item__label">
									{ __( 'Dimensions', 'woocommerce' ) }
								</th>
								<td className="woocommerce-product-attributes-item__value">
									{ `${ product.dimensions.length } × ${ product.dimensions.width } × ${ product.dimensions.height }` }
								</td>
							</tr>
						) }

					{ /* Display Product Attributes */ }
					{ product.attributes?.map( ( attribute ) => (
						<tr
							key={ attribute.id }
							className={ `woocommerce-product-attributes-item woocommerce-product-attributes-item--${ attribute.name.toLowerCase() }` }
						>
							<th className="woocommerce-product-attributes-item__label">
								{ attribute.name }
							</th>
							<td className="woocommerce-product-attributes-item__value">
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
