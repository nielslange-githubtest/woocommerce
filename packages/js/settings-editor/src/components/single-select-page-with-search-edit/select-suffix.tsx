/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { close } from '@wordpress/icons';

type SelectSuffixProps = {
	isLoading: boolean;
	isFetching: boolean;
	value: string;
	onRemove: () => void;
};

export const SelectSuffix = ( {
	isLoading,
	isFetching,
	value,
	onRemove,
}: SelectSuffixProps ) => {
	if ( isLoading || isFetching ) {
		return <Spinner />;
	}

	if ( value ) {
		return (
			<Button
				icon={ close }
				onClick={ onRemove }
				iconSize={ 16 }
				size="compact"
			/>
		);
	}

	return null;
};
