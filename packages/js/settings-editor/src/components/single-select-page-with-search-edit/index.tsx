/**
 * External dependencies
 */
import {
	createElement,
	useCallback,
	useMemo,
	useState,
} from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
	useAsyncFilter,
} from '@woocommerce/components';
import { Spinner, BaseControl } from '@wordpress/components';
import { useSelect, resolveSelect } from '@wordpress/data';
import { store as coreDataStore, Page } from '@wordpress/core-data';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';
import { sanitizeHTML } from '../../utils';
import { SelectSuffix } from './select-suffix';

/**
 * The type for the page item for the select control.
 */
type PageItem = {
	label: string;
	value: string;
};

type SingleSelectPageWithSearchEditProps =
	DataFormControlProps< DataFormItem > & {
		help?: React.ReactNode;
		className?: string;
		/**
		 * The pages to exclude from the search results.
		 */
		exclude?: string[];
	};

/**
 * The formatPageToItem function is used to format a page to an item.
 *
 * @param page - The page to format.
 * @return The formatted item.
 */
const formatPageToItem = ( page: Page ): PageItem => ( {
	label: sprintf(
		/* translators: 1: page name 2: page ID */
		__( '%1$s (ID: %2$s)', 'woocommerce' ),
		page.title.rendered,
		page.id
	),
	value: page.id.toString(),
} );

/**
 * The useSelectedItem hook is used to get the selected page item.
 *
 * @param value - The value of the selected page item.
 * @return The selected page item and a boolean indicating if the page is loading.
 */
const useSelectedItem = ( value: string ) => {
	const { selectedPage, isLoading } = useSelect(
		( select ) => {
			if ( ! value ) {
				return { selectedPage: null, isLoading: false };
			}

			const { getEntityRecord, hasFinishedResolution } =
				select( coreDataStore );

			const args: [ 'postType', 'page', string ] = [
				'postType',
				'page',
				value,
			];

			return {
				selectedPage: getEntityRecord< Page >( ...args ),
				isLoading: ! hasFinishedResolution( 'getEntityRecord', args ),
			};
		},
		[ value ]
	);

	const selectedItem = useMemo(
		() => ( selectedPage ? formatPageToItem( selectedPage ) : null ),
		[ selectedPage ]
	);

	return {
		selectedItem,
		isLoading,
	};
};

/**
 * The usePageSearch hook is used to search for pages.
 *
 * @param selectedItem - The selected page item.
 * @param exclude      - The pages to exclude from the search results.
 * @return The searched pages and a boolean indicating if the pages are loading.
 */
const usePageSearch = ( selectedItem: PageItem | null, exclude?: string[] ) => {
	const [ searchedItems, setSearchedItems ] = useState< PageItem[] >( [] );

	const handleFilter = useCallback(
		async ( search?: string ) => {
			if (
				! search ||
				search.trim() === '' ||
				// When the selected item is the same as the search term, don't call the API to avoid unnecessary requests.
				search === selectedItem?.label
			) {
				return [];
			}

			const records = ( await resolveSelect(
				coreDataStore
			).getEntityRecords( 'postType', 'page', {
				search,
				exclude,
				status: [ 'publish', 'private', 'draft' ],
			} ) ) as Page[];

			return records?.map( formatPageToItem ) || [];
		},
		[ exclude, selectedItem ]
	);

	const onFilterStart = useCallback( () => {
		setSearchedItems( [] );
	}, [] );

	const onFilterEnd = useCallback( ( items: PageItem[] ) => {
		setSearchedItems( items );
	}, [] );

	const { isFetching, onInputChange, getFilteredItems } =
		useAsyncFilter< PageItem >( {
			filter: handleFilter,
			onFilterStart,
			onFilterEnd,
		} );

	return {
		searchedItems,
		isFetching,
		onInputChange,
		getFilteredItems,
	};
};

export const SingleSelectPageWithSearchEdit = ( {
	data,
	field,
	onChange,
	hideLabelFromVision,
	help,
	className,
	exclude,
}: SingleSelectPageWithSearchEditProps ) => {
	const value = field.getValue( { item: data } ) ?? '';
	const { id } = field;

	const { selectedItem, isLoading } = useSelectedItem( value );

	const { searchedItems, isFetching, onInputChange, getFilteredItems } =
		usePageSearch( selectedItem, exclude );

	const handleSelect = useCallback(
		( item: PageItem | null ) => {
			onChange( { [ id ]: item?.value || '' } );
		},
		[ onChange, id ]
	);

	const label =
		field.label === id ? undefined : (
			<div
				dangerouslySetInnerHTML={ {
					__html: sanitizeHTML( field.label ),
				} }
			/>
		);

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id={ id }
			help={ help }
			label={ label }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<input type="hidden" id={ id } value={ value } />
			<SelectControl< PageItem >
				className={ className }
				placeholder={ __( 'Search for a page…', 'woocommerce' ) }
				label=""
				// The select control input does not require an id since its value represents the label (which displays the page title to the user). A hidden input with the actual id is provided above, ensuring that the value is saved correctly.
				inputProps={ { id: undefined } }
				items={ searchedItems }
				selected={ selectedItem }
				onSelect={ handleSelect }
				onRemove={ () => handleSelect( null ) }
				suffix={
					<SelectSuffix
						isLoading={ isLoading }
						isFetching={ isFetching }
						value={ value }
						onRemove={ () => handleSelect( null ) }
					/>
				}
				onInputChange={ onInputChange }
				getFilteredItems={ getFilteredItems }
			>
				{ ( {
					items,
					isOpen,
					highlightedIndex,
					getItemProps,
					getMenuProps,
				} ) => (
					<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
						{ isFetching ? (
							<Spinner />
						) : (
							items.map( ( item, index: number ) => (
								<MenuItem
									key={ `${ item.value }${ index }` }
									index={ index }
									isActive={ highlightedIndex === index }
									item={ item }
									getItemProps={ getItemProps }
								>
									{ item.label }
								</MenuItem>
							) )
						) }
					</Menu>
				) }
			</SelectControl>
		</BaseControl>
	);
};
