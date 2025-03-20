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
import type {
	ProductGalleryState,
	ImageDataItem,
	ProductGalleryStore,
} from './types';

const getContext = ( ns?: string ) => getContextFn< ProductGalleryState >( ns );

const getArrowsState = ( imageId: number ) => ( {
	disableLeft: imageId === state.allImageIds[ 0 ],
	disableRight: imageId === state.allImageIds[ state.allImageIds.length - 1 ],
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

const productGallery: ProductGalleryStore = {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - State properties are initialized via PHP's wp_interactivity_state
	state: {
		/**
		 * The IDs of all images.
		 *
		 * @return {number[]} The IDs of all images.
		 */
		get allImageIds(): number[] {
			return ( state.imageData || [] ).map(
				( image: ImageDataItem ) => image.id
			);
		},
		// TODO: This is a temporary solution to display the view all thumbnail.
		/**
		 * The subset of numberOfThumbnails that are displayed in the thumbnails block.
		 *
		 * @return {ImageDataItem[]} The subset of thumbnails.
		 */
		thumbnails: (): ImageDataItem[] | undefined => {
			const { numberOfThumbnails } = getConfig();
			return state.imageData?.slice( 0, numberOfThumbnails );
		},
	},
	actions: {
		selectImage: ( newImageId: number ) => {
			const { disableLeft, disableRight } = getArrowsState( newImageId );

			state.disableLeft = disableLeft;
			state.disableRight = disableRight;

			state.selectedImageId = newImageId;

			scrollImageIntoView( newImageId );
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
			actions.selectImage( imageId );
		},
		selectNextImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const currentIndex = state.allImageIds.indexOf(
				state.selectedImageId
			);
			const nextIndex = Math.min(
				currentIndex + 1,
				state.allImageIds.length - 1
			);
			const nextImageId = state.allImageIds[ nextIndex ];

			actions.selectImage( nextImageId );
		},
		selectPreviousImage: ( event?: MouseEvent ) => {
			if ( event ) {
				event.stopPropagation();
			}

			const currentIndex = state.allImageIds.indexOf(
				state.selectedImageId
			);
			const previousIndex = Math.max( 0, currentIndex - 1 );
			const previousImageId = state.allImageIds[ previousIndex ];

			actions.selectImage( previousImageId );
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
				withScope( () =>
					actions.selectImage( state.allImageIds[ 0 ] )
				);

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
							actions.selectImage( currentImageId );
						} else {
							actions.selectImage( state.allImageIds[ 0 ] );
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
