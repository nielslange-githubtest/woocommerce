/**
 * External dependencies
 */
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import {
	InnerBlocks,
	useInnerBlocksProps,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { parse } from '@wordpress/blocks';

const NonEditableTemplatePartPreview = ( {
	blockProps,
	templatePartId,
}: {
	blockProps: Record< string, unknown >;
	templatePartId: string | undefined;
} ) => {
	const [ isBlockEditingModeDisabled, setIsBlockEditingModeDisabled ] =
		useState( false );

	const { content, editedBlocks } = useSelect(
		( select ) => {
			if ( ! templatePartId ) {
				return {};
			}
			const { getEditedEntityRecord } = select( coreStore );
			const editedRecord = getEditedEntityRecord(
				'postType',
				'wp_template_part',
				templatePartId
			);
			return {
				editedBlocks: editedRecord?.blocks,
				content: editedRecord?.content,
			};
		},
		[ templatePartId ]
	);

	const blocks = useMemo( () => {
		if ( ! templatePartId ) {
			return undefined;
		}

		if ( editedBlocks ) {
			return editedBlocks;
		}

		if ( ! content || typeof content !== 'string' ) {
			return [];
		}

		return parse( content );
	}, [ templatePartId, editedBlocks, content ] );

	const { setBlockEditingMode, unsetBlockEditingMode } =
		useDispatch( blockEditorStore );

	useEffect( () => {
		blocks.forEach( ( block ) => {
			setBlockEditingMode( block.clientId, 'disabled' );
		} );
		setIsBlockEditingModeDisabled( true );
		return () => {
			blocks.forEach( ( block ) => {
				unsetBlockEditingMode( block.clientId );
			} );
			setIsBlockEditingModeDisabled( false );
		};
	}, [ setBlockEditingMode, unsetBlockEditingMode, blocks ] );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput: () => {},
		onChange: () => {},
		renderAppender: null,
	} );

	if (
		! isBlockEditingModeDisabled ||
		! Array.isArray( blocks ) ||
		blocks.length === 0
	) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
};

const TemplatePartInnerBlocks = ( {
	blockProps,
	templatePartId,
}: {
	blockProps: Record< string, unknown >;
	templatePartId: string | undefined;
} ) => {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'wp_template_part',
		{ id: templatePartId }
	);

	const { isLoading } = useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( coreStore );

			const hasResolvedEntity = templatePartId
				? hasFinishedResolution( 'getEditedEntityRecord', [
						'postType',
						'wp_template_part',
						templatePartId,
				  ] )
				: false;

			return {
				isLoading: ! hasResolvedEntity,
			};
		},
		[ templatePartId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput,
		onChange,
		renderAppender: () =>
			! isLoading && blocks.length === 0
				? InnerBlocks.ButtonBlockAppender
				: null,
	} );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
};

export const AddToCartWithOptionsEditTemplatePart = ( {
	productType,
}: {
	productType: string;
} ) => {
	const { templatePartId } = useSelect(
		( select ) => {
			const {
				getCurrentTheme,
				getEditedEntityRecord,
				hasFinishedResolution,
			} = select( coreStore );

			const currentTheme = getCurrentTheme()?.stylesheet;

			if ( ! currentTheme ) {
				return {
					templatePartId: null,
				};
			}

			const templatePartSlug = `${ productType }-product-add-to-cart-with-options`;
			const themeTemplatePartId = `${ currentTheme }//${ templatePartSlug }`;
			const wooCommerceTemplatePartId = `woocommerce/woocommerce//${ templatePartSlug }`;

			const getThemeEntityArgs = [
				'postType',
				'wp_template_part',
				themeTemplatePartId,
			] as const;
			const themeEntityRecord = themeTemplatePartId
				? getEditedEntityRecord( ...getThemeEntityArgs )
				: null;
			const hasResolvedEntity = themeTemplatePartId
				? hasFinishedResolution(
						'getEditedEntityRecord',
						getThemeEntityArgs
				  )
				: false;

			const themeTemplatePartIsMissing =
				hasResolvedEntity &&
				( ! themeEntityRecord ||
					Object.keys( themeEntityRecord ).length === 0 );

			return {
				templatePartId: themeTemplatePartIsMissing
					? wooCommerceTemplatePartId
					: themeTemplatePartId,
			};
		},
		[ productType ]
	);

	const blockProps = useBlockProps();

	const { canViewTemplatePart, canEditTemplatePart } = useSelect(
		( select ) => {
			const templatePartData = {
				kind: 'postType',
				name: 'wp_template_part',
				id: templatePartId,
			};
			return {
				canViewTemplatePart: !! select( coreStore ).canUser(
					'read',
					templatePartData
				),
				canEditTemplatePart: !! select( coreStore ).canUser(
					'update',
					templatePartData
				),
			};
		},
		[ templatePartId ]
	);

	if ( ! templatePartId ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	if ( ! canViewTemplatePart ) {
		return null;
	}

	return canEditTemplatePart ? (
		<TemplatePartInnerBlocks
			blockProps={ blockProps }
			templatePartId={ templatePartId }
		/>
	) : (
		<NonEditableTemplatePartPreview
			blockProps={ blockProps }
			templatePartId={ templatePartId }
		/>
	);
};
