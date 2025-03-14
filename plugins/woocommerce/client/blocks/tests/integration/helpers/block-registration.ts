/**
 * External dependencies
 */
import { getBlockType, registerBlockType } from '@wordpress/blocks';

/**
 * Registers a test block for testing purposes.
 * @param relativePath - The relative path of the block (e.g., 'active-filters' or 'removable-chips')
 * @param blockName    - Custom block name.
 * @throws Error if block metadata or edit function cannot be imported
 */
export async function registerTestBlock(
	relativePath: string,
	blockName: string
) {
	// Skip if block is already registered
	if ( getBlockType( blockName ) ) {
		return;
	}

	try {
		// Dynamic imports for metadata and edit function
		const metadata = await import(
			`../../../assets/js/blocks/${ relativePath }/block.json`
		);
		const { default: edit } = await import(
			`../../../assets/js/blocks/${ relativePath }/edit`
		);

		// Register the block
		const { category, ...rest } = metadata;
		registerBlockType( rest, { edit } );
	} catch ( error ) {
		throw new Error(
			`Failed to register block ${ blockName }: ${ error.message }`
		);
	}
}
