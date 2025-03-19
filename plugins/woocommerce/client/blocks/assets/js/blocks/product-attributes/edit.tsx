/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

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

	return isSpecificProductContext ? (
		<div { ...blockProps }>
			{ /* TODO Temporary placeholder */ }
			<div>Product Attributes Content</div>
		</div>
	) : (
		<Placeholder />
	);
};

export default Edit;
