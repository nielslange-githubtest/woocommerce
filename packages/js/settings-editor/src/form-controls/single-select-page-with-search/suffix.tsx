/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { close } from '@wordpress/icons';

type SuffixProps = {
	isLoading: boolean;
	isFetching: boolean;
	value: string;
	onRemove: () => void;
};

export const Suffix = ( {
	isLoading,
	isFetching,
	value,
	onRemove,
}: SuffixProps ) => {
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
