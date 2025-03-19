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

interface ImageData {
	images: ImageDataItem[];
}

export interface ProductGalleryContext {
	selectedImageId: number;
	isDialogOpen: boolean;
	productId: string;
	disableLeft: boolean;
	disableRight: boolean;
	touchStartX: number;
	touchCurrentX: number;
	isDragging: boolean;
	imageData: ImageData;
	image: ImageDataItem;
}
