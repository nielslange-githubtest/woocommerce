export interface ProductGalleryBlockAttributes {
	cropImages: boolean;
	hoverZoom: boolean;
	fullScreenOnClick: boolean;
}

export interface ProductGallerySettingsProps {
	attributes: ProductGalleryBlockAttributes;
	setAttributes: (
		attributes: Partial< ProductGalleryBlockAttributes >
	) => void;
}

export interface ImageDataItem {
	id: number;
	src: string;
	srcSet: string;
	sizes: string;
}

export interface ProductGalleryState {
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

export interface ProductGalleryStore {
	state: ProductGalleryState;
	actions: StoreActions;
	callbacks: StoreCallbacks;
}
