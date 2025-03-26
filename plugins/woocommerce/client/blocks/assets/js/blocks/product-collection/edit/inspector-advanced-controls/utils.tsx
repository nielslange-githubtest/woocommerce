/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { getBlockSupport } from '@wordpress/blocks';

export const useUnsupportedBlocks = ( clientId: string ): boolean =>
	useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet
			const { getClientIdsOfDescendants, getBlockName } =
				select( blockEditorStore );

			const hasUnsupportedBlocks =
				getClientIdsOfDescendants( clientId ).find(
					( blockId: string ) => {
						/*
						 * Client side navigation can be true in two states:
						 *  - supports.interactivity = true;
						 *  - supports.interactivity.clientNavigation = true;
						 */
						const blockName = getBlockName( blockId );
						const blockSupportsInteractivity = Object.is(
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore interactivity is not typed parameter of BlockSupports
							getBlockSupport( blockName, 'interactivity' ),
							true
						);
						const blockSupportsInteractivityClientNavigation =
							getBlockSupport(
								blockName,
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore interactivity.clientNavigation is not typed parameter of BlockSupports
								'interactivity.clientNavigation'
							);
						const supported =
							blockSupportsInteractivity ||
							blockSupportsInteractivityClientNavigation;
						return ! supported;
					}
				) || false;

			return hasUnsupportedBlocks;
		},
		[ clientId ]
	);
