/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './product-preview-modal.scss';

interface ProductPreviewModalProps {
	productTitle: string;
	productVendor: JSX.Element | string | null;
	productIcon: string;
	onOpen?: () => void;
	onClose?: () => void;
}

export default function ProductPreviewModal( {
	productTitle,
	productVendor,
	productIcon,
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

	const productHeader = (
		<div className="woocommerce-marketplace__product-preview-modal__header">
			<img
				className="woocommerce-marketplace__product-preview-modal__icon"
				src={ productIcon }
				alt={ productTitle }
			/>
			<div className="woocommerce-marketplace__product-preview-modal__header-content">
				<h1>{ productTitle }</h1>
				{ productVendor && (
					<div className="woocommerce-marketplace__product-preview-modal__vendor">
						<span>{ __( 'By ', 'woocommerce' ) }</span>
						{ productVendor }
					</div>
				) }
			</div>
		</div>
	);

	return (
		<Modal
			onRequestClose={ closeModal }
			className="woocommerce-marketplace__product-preview-modal"
			closeButtonLabel={ __( 'Close product preview', 'woocommerce' ) }
			size="large"
			focusOnMount="firstElement"
		>
			{ productHeader }
			<hr className="woocommerce-marketplace__product-preview-modal__divider" />
			<div className="woocommerce-marketplace__product-preview-modal__content">
				{ /* TODO: Fetch and display product preview using AJAX */ }
				{ /* TODO: Add a loading state */ }
				{ /* TODO: Add a fallback content if AJAX fails */ }
				<p>
					{ __( 'This is a preview of the product.', 'woocommerce' ) }
				</p>
			</div>
		</Modal>
	);
}
