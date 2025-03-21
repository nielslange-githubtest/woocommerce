/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { rawHandler, serialize } from '@wordpress/blocks';

/**
 * Mapping of block names to class names
 */
const GROUP_BLOCK_NAMES_AND_CLASS_NAMES_MAP = {
	'Gallery Area': 'wc-block-product-gallery__gallery-area',
	'Large Image and Navigation': 'wc-block-product-gallery__large-image-and-navigation',
};

/**
 * Add class names to the blocks based on the block name
 * @param blocks - The blocks to add class names to
 * @returns The blocks with the added class names
 */
function addGalleryClassNames(blocks: any[]): any[] {
    return blocks.map(block => {
        const updatedBlock = { ...block };
        
        // Check if this block's name exists in our mapping
        const blockName = block.attributes?.metadata?.name;
        const className = GROUP_BLOCK_NAMES_AND_CLASS_NAMES_MAP[blockName as keyof typeof GROUP_BLOCK_NAMES_AND_CLASS_NAMES_MAP];
        
        if (className) {
            // Add className if it doesn't exist
            if (!block.attributes.className?.includes(className)) {
                updatedBlock.attributes = {
                    ...updatedBlock.attributes,
                    className: block.attributes.className
                        ? `${block.attributes.className} ${className}`
                        : className
                };
            }
        }

        // Recursively process innerBlocks if they exist
        if (block.innerBlocks && block.innerBlocks.length > 0) {
            updatedBlock.innerBlocks = addGalleryClassNames(block.innerBlocks);
        }

        return updatedBlock;
    });
}

export const Save = () => {
	const blockProps = useBlockProps.save({
		className: 'wc-block-product-gallery',
	});
	const innerBlocksProps = useInnerBlocksProps.save(blockProps);
	const parsedBlocks = rawHandler({ HTML: innerBlocksProps.children.props.children });
	const updatedParsedBlocks = addGalleryClassNames(parsedBlocks);
	const serializedUpdatedBlocks = serialize(updatedParsedBlocks);

	// Create new props object with the updated children
	const updatedInnerBlocksProps = {
		...innerBlocksProps,
		children: {
			...innerBlocksProps.children,
			props: {
				...innerBlocksProps.children.props,
				children: serializedUpdatedBlocks
			}
		}
	};

	return <div {...updatedInnerBlocksProps} />;
};
