/**
 * External dependencies
 */
import { useCallback, useState, useMemo } from '@wordpress/element';
import { resolveSelect, useSelect } from '@wordpress/data';
import { store as coreDataStore, Page } from '@wordpress/core-data';
import { useAsyncFilter } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import type { PageItem } from './types';
import { formatPageToItem } from './utils';

type UsePageSearchReturn = {
	searchedItems: PageItem[];
	isFetching: boolean;
} & Pick<
	ReturnType< typeof useAsyncFilter< PageItem > >,
	'onInputChange' | 'getFilteredItems'
>;

/**
 * The usePageSearch hook is used to search for pages.
 *
 * @param selectedItem - The selected page item.
 * @param exclude      - The pages to exclude from the search results.
 * @return The searched pages and a boolean indicating if the pages are loading.
 */
export const usePageSearch = (
	selectedItem: PageItem | null,
	exclude?: string[]
): UsePageSearchReturn => {
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

/**
 * The useSelectedItem hook is used to get the selected page item.
 *
 * @param value - The value of the selected page item.
 * @return The selected page item and a boolean indicating if the page is loading.
 */
export const useSelectedItem = ( value: string ) => {
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
