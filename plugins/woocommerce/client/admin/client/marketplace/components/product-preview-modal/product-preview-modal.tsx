/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { fetchProductPreview } from '../../utils/functions';
import './product-preview-modal.scss';

interface ProductPreviewModalProps {
	productTitle: string;
	productVendor: JSX.Element | string | null;
	productIcon: string;
	productId: number;
	onOpen?: () => void;
	onClose?: () => void;
}

export default function ProductPreviewModal( {
	productTitle,
	productVendor,
	productIcon,
	productId,
	onOpen,
	onClose,
}: ProductPreviewModalProps ) {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ previewContent, setPreviewContent ] = useState< {
		html: string;
		css: string;
	} | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	// Fetch preview content and record event when the modal mounts
	useEffect( () => {
		const loadPreview = async () => {
			try {
				const response = await fetchProductPreview( productId );
				const previewData = response?.data || response;

				if ( ! previewData?.html || ! previewData?.css ) {
					throw new Error( 'Invalid preview data structure' );
				}

				setPreviewContent( {
					html: previewData.html,
					css: previewData.css,
				} );
				setError( null );
			} catch ( err ) {
				setError(
					__( 'Failed to load product preview.', 'woocommerce' )
				);
			} finally {
				setIsLoading( false );
			}
		};

		loadPreview();
		if ( onOpen ) {
			onOpen();
		}
	}, [ onOpen, productId ] );

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
				{ isLoading && (
					<div className="woocommerce-marketplace__product-preview-modal__loading">
						{ __( 'Loading preview…', 'woocommerce' ) }
					</div>
				) }
				{ error && (
					<div className="woocommerce-marketplace__product-preview-modal__error">
						{ error }
					</div>
				) }
				{ ! isLoading && ! error && previewContent && (
					<>
						<style>{ previewContent.css }</style>
						<div
							dangerouslySetInnerHTML={ {
								__html: previewContent.html,
							} }
						/>
					</>
				) }
			</div>
		</Modal>
	);
}
