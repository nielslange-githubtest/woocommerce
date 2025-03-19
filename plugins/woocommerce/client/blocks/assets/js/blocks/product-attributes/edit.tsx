/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ProductResponseItem } from '@woocommerce/types';
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

	const { results, isLoading } = useCollection< ProductResponseItem >( {
		namespace: '/wc/store/v1',
		resourceName: 'products',
		resourceValues: [ Number( postId ) ],
		shouldSelect: !! postId,
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

	// Handle both single item and array responses
	const product = Array.isArray( results ) ? results[ 0 ] : results;

	if ( postId && ! product ) {
		return (
			<div { ...blockProps }>
				<p>{ __( 'No product found', 'woocommerce' ) }</p>
			</div>
		);
	}

	console.log( 'product', product );

	return isSpecificProductContext ? (
		<div { ...blockProps }>
			<table className="woocommerce-product-attributes shop_attributes">
				<tbody>
					{ /* Display Product Attributes */ }
					{ product.attributes.map( ( attribute ) => (
						<tr
							key={ attribute.id }
							className={ `woocommerce-product-attributes-item woocommerce-product-attributes-item--${ attribute.name.toLowerCase() }` }
						>
							<th className="woocommerce-product-attributes-item__label">
								{ attribute.name }
							</th>
							<td className="woocommerce-product-attributes-item__value">
								{ attribute.terms
									.map( ( term ) => term.name )
									.join( ', ' ) }
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
