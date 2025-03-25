/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

interface ProductPreviewModalProps {
	productTitle: string;
	productVendorName: string;
	onOpen?: () => void;
	onClose?: () => void;
}

export default function ProductPreviewModal( {
	productTitle,
	productVendorName,
	onOpen,
	onClose,
}: ProductPreviewModalProps ) {
	// Record event when the modal mounts
	useEffect( () => {
		if ( onOpen ) {
			onOpen();
		}
	}, [ onOpen ] );

	const closeModal = () => {
		if ( onClose ) {
			onClose();
		}
	};

	return (
		<Modal
			onRequestClose={ closeModal }
			className="woocommerce-marketplace__product-preview-modal"
			closeButtonLabel={ __( 'Close product preview', 'woocommerce' ) }
			size="large"
			focusOnMount="firstElement"
		>
			<div>
				<h1>{ productTitle }</h1>
				<p>{ productVendorName }</p>
			</div>
		</Modal>
	);
}
