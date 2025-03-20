/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	getElement,
	withScope,
	getConfig,
} from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductGalleryContext, ImageDataItem } from './types';

interface StoreState {
	imageData: ImageDataItem[] | undefined;
	selectedImageId: number;
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	readonly allImageIds: number[];
	readonly selectedImageNumber: number;
	thumbnails: () => ImageDataItem[] | undefined;
}

interface StoreActions {
	selectImage: ( newImageNumber: number ) => void;
	selectCurrentImage: ( event?: MouseEvent ) => void;
	selectNextImage: ( event?: MouseEvent ) => void;
	selectPreviousImage: ( event?: MouseEvent ) => void;
	onSelectedLargeImageKeyDown: ( event: KeyboardEvent ) => void;
	onViewAllImagesKeyDown: ( event: KeyboardEvent ) => void;
	onThumbnailKeyDown: ( event: KeyboardEvent ) => void;
	onDialogKeyDown: ( event: KeyboardEvent ) => void;
	openDialog: () => void;
	closeDialog: () => void;
	onTouchStart: ( event: TouchEvent ) => void;
	onTouchMove: ( event: TouchEvent ) => void;
	onTouchEnd: () => void;
	displayViewAll: () => boolean;
}

interface StoreCallbacks {
	watchForChangesOnAddToCartForm: () => void;
	dialogStateChange: () => void;
	toggleActiveImageAtrributes: () => void;
}

interface ProductGalleryStore {
	state: StoreState;
	actions: StoreActions;
	callbacks: StoreCallbacks;
}

const getContext = ( ns?: string ) =>
	getContextFn< ProductGalleryContext >( ns );

const getArrowsState = ( imageNumber: number, totalImages: number ) => ( {
	// One-based index so it ranges from 1 to imagesIds.length.
	disableLeft: imageNumber === 1,
	disableRight: imageNumber === totalImages,
} );

/**
 * Scrolls an image into view.
 *
 * @param {string} imageId - The ID of the image to scroll into view.
 */
const scrollImageIntoView = ( imageId: number ) => {
	if ( ! imageId ) {
		return;
	}
	const imageElement = document.querySelector(
		`.wp-block-woocommerce-product-gallery-large-image img[data-image-id="${ imageId }"]`
	);
	if ( imageElement ) {
		imageElement.scrollIntoView( {
			behavior: 'smooth',
			block: 'nearest',
			inline: 'center',
		} );
	}
};

/**
 * Gets the number of the active image.
 *
 * @param {number[]} imageIds        - The IDs of the images.
 * @param {number}   selectedImageId - The ID of the selected image.
 * @return {number} The number of the active image.
 */
const getSelectedImageNumber = (
	imageIds: number[],
	selectedImageId: number
) => imageIds.indexOf( selectedImageId ) + 1;

const productGallery: ProductGalleryStore = {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - State properties are initialized via PHP's wp_interactivity_state
	state: {
		get allImageIds(): number[] {
			return ( state.imageData || [] ).map(
				( image: ImageDataItem ) => image.id
			);
		},
		/**
		 * The number of the active image. Not to be confused with the index of the active image in the imageIds array.
		 *
		 * @return {number} The number of the active image.
		 */
		get selectedImageNumber(): number {
			const { selectedImageId } = getContext();
			return getSelectedImageNumber( state.allImageIds, selectedImageId );
		},
		// TODO: This is a temporary solution to display the view all thumbnail.
		// Will eventually be replaced by a slider where processedImageData can be used directly.
		/**
		 * The subset of processedImageData that is displayed in the thumbnails block.
		 *
		 * @return Array The subset of processed image data.
		 */
		thumbnails: (): ImageDataItem[] | undefined => {
			const { numberOfThumbnails } = getConfig();
			return state.imageData?.slice( 0, numberOfThumbnails );
		},
	},
	actions: {
		selectImage: ( newImageNumber: number ) => {
			const { disableLeft, disableRight } = getArrowsState(
				newImageNumber,
				state.allImageIds.length
			);

			state.disableLeft = disableLeft;
			state.disableRight = disableRight;

			const imageIndex = newImageNumber - 1;
			const imageId = state.allImageIds[ imageIndex ];

			state.selectedImageId = imageId;

			if ( imageIndex !== -1 ) {
				scrollImageIntoView( imageId );
			}
		},
		selectCurrentImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) {
				return;
			}
			const imageIdValue = element.getAttribute( 'data-image-id' );
			if ( ! imageIdValue ) {
				return;
			}
			const imageId = parseInt( imageIdValue, 10 );
			const newImageNumber = state.allImageIds.indexOf( imageId ) + 1;
			actions.selectImage( newImageNumber );
		},
		selectNextImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const selectedImageNumber = getSelectedImageNumber(
				state.allImageIds,
				state.selectedImageId
			);
			const newImageNumber = Math.min(
				state.allImageIds.length,
				selectedImageNumber + 1
			);

			actions.selectImage( newImageNumber );
		},
		selectPreviousImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const selectedImageNumber = getSelectedImageNumber(
				state.allImageIds,
				state.selectedImageId
			);
			const newImageNumber = Math.max( 1, selectedImageNumber - 1 );

			actions.selectImage( newImageNumber );
		},
		onSelectedLargeImageKeyDown: ( event: KeyboardEvent ) => {
			if (
				event.code === 'Enter' ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}

			if ( event.code === 'ArrowRight' ) {
				actions.selectNextImage();
			}

			if ( event.code === 'ArrowLeft' ) {
				actions.selectPreviousImage();
			}
		},
		onViewAllImagesKeyDown: ( event: KeyboardEvent ) => {
			if (
				event.code === 'Enter' ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.openDialog();
			}
		},
		onThumbnailKeyDown: ( event: KeyboardEvent ) => {
			if (
				event.code === 'Enter' ||
				event.code === 'Space' ||
				event.code === 'NumpadEnter'
			) {
				if ( event.code === 'Space' ) {
					event.preventDefault();
				}
				actions.selectCurrentImage();
			}
		},
		onDialogKeyDown: ( event: KeyboardEvent ) => {
			if ( event.code === 'Escape' ) {
				actions.closeDialog();
			}
		},
		openDialog: () => {
			state.isDialogOpen = true;
			document.body.classList.add(
				'wc-block-product-gallery-dialog-open'
			);
		},
		closeDialog: () => {
			state.isDialogOpen = false;
			document.body.classList.remove(
				'wc-block-product-gallery-dialog-open'
			);
		},
		onTouchStart: ( event: TouchEvent ) => {
			const { clientX } = event.touches[ 0 ];
			state.touchStartX = clientX;
			state.touchCurrentX = clientX;
			state.isDragging = true;
		},
		onTouchMove: ( event: TouchEvent ) => {
			if ( ! state.isDragging ) {
				return;
			}
			const { clientX } = event.touches[ 0 ];
			state.touchCurrentX = clientX;
			event.preventDefault();
		},
		onTouchEnd: () => {
			if ( ! state.isDragging ) {
				return;
			}

			const SNAP_THRESHOLD = 0.2;
			const delta = state.touchCurrentX - state.touchStartX;
			const element = getElement()?.ref as HTMLElement;
			const imageWidth = element?.offsetWidth || 0;

			// Only trigger swipe actions if there was significant movement
			if ( Math.abs( delta ) > imageWidth * SNAP_THRESHOLD ) {
				if ( delta > 0 && ! state.disableLeft ) {
					actions.selectPreviousImage();
				} else if ( delta < 0 && ! state.disableRight ) {
					actions.selectNextImage();
				}
			}

			// Reset touch state
			state.isDragging = false;
			state.touchStartX = 0;
			state.touchCurrentX = 0;
		},
		// TODO: This is a temporary solution to display the view all thumbnail.
		// Will eventually be replaced by a slider.
		displayViewAll: () => {
			const { numberOfThumbnails } = getConfig();
			const allImages = state.imageData;
			if ( ! allImages || allImages.length <= numberOfThumbnails ) {
				return false;
			}
			const lastThumbnail = allImages[ numberOfThumbnails - 1 ];
			const context = getContext();
			return context.image.id === lastThumbnail.id;
		},
	},
	callbacks: {
		watchForChangesOnAddToCartForm: () => {
			const variableProductCartForm = document.querySelector(
				`form[data-product_id="${ state.productId }"]`
			);

			if ( ! variableProductCartForm ) {
				return;
			}

			const selectFirstImage = () =>
				withScope( () => actions.selectImage( 1 ) );

			const observer = new MutationObserver(
				withScope( function ( mutations ) {
					for ( const mutation of mutations ) {
						const mutationTarget = mutation.target as HTMLElement;
						const currentImageAttribute =
							mutationTarget.getAttribute( 'current-image' );
						const currentImageId = currentImageAttribute
							? parseInt( currentImageAttribute, 10 )
							: null;
						if (
							mutation.type === 'attributes' &&
							currentImageId &&
							state.allImageIds.includes( currentImageId )
						) {
							const nextImageNumber =
								state.allImageIds.indexOf( currentImageId ) + 1;

							actions.selectImage( nextImageNumber );
						} else {
							actions.selectImage( 1 );
						}
					}
				} )
			);

			observer.observe( variableProductCartForm, {
				attributes: true,
			} );

			const clearVariationsLink = document.querySelector(
				'.wp-block-add-to-cart-form .reset_variations'
			);

			if ( clearVariationsLink ) {
				clearVariationsLink.addEventListener(
					'click',
					selectFirstImage
				);
			}

			return () => {
				observer.disconnect();
				document.removeEventListener( 'click', selectFirstImage );
			};
		},
		dialogStateChange: () => {
			const { selectedImageId, isDialogOpen } = state;
			const { ref: dialogRef } = getElement() || {};

			if ( isDialogOpen && dialogRef instanceof HTMLElement ) {
				dialogRef.focus();
				const selectedImage = dialogRef.querySelector(
					`[data-image-id="${ selectedImageId }"]`
				);

				if ( selectedImage instanceof HTMLElement ) {
					selectedImage.scrollIntoView( {
						behavior: 'auto',
						block: 'center',
					} );
					selectedImage.focus();
				}
			}
		},
		toggleActiveImageAtrributes: () => {
			const element = getElement()?.ref as HTMLElement;
			if ( ! element ) return false;

			const imageIdValue = element.getAttribute( 'data-image-id' );
			if ( ! imageIdValue ) return false;

			const imageId = parseInt( imageIdValue, 10 );
			if ( state.selectedImageId === imageId ) {
				element.classList.add( 'is-active' );
				element.setAttribute( 'tabIndex', '0' );
			} else {
				element.setAttribute( 'tabIndex', '-1' );
			}
		},
	},
};

const { state, actions } = store(
	'woocommerce/product-gallery',
	productGallery,
	{ lock: true }
);

export type Store = ProductGalleryStore;
