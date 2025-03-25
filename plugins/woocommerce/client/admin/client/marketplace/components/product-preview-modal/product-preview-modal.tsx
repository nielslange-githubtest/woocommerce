/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { Modal } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */

interface ProductPreviewModalProps {
	productId: string;
	productTitle: string;
	productVendorName: string;
	productType: string;
	tab: string;
}

export default function ProductPreviewModal( {
	productId,
	productTitle,
	productVendorName,
	productType,
	tab,
}: ProductPreviewModalProps ) {
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	useEffect( () => {
		if ( isModalOpen ) {
			recordEvent( 'marketplace_product_preview_notice_opened', {
				product_id: productId,
				product_name: productTitle,
				vendor: productVendorName,
				product_type: productType,
				tab,
			} );
		}
	}, [
		isModalOpen,
		productId,
		productTitle,
		productVendorName,
		productType,
		tab,
	] );

	const closeModal = () => {
		setIsModalOpen( false );
		recordEvent( 'marketplace_product_preview_modal_dismissed', {
			product_id: productId,
			product_name: productTitle,
			vendor: productVendorName,
			product_type: productType,
			tab,
		} );
	};

	return (
		<Modal
			onRequestClose={ closeModal }
			className="woocommerce-marketplace__product-preview-modal"
		>
			<div>
				<h1>Product Preview</h1>
			</div>
		</Modal>
	);
}
