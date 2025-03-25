/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { useState, useRef } from '@wordpress/element';
import { Icon, dragHandle } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './bacs-account-details-table.scss';

interface BacsAccountDetails {
	account_name: string;
	account_number: string;
	bank_name: string;
	sort_code: string;
	iban: string;
	bic: string;
}

export const BacsAccountDetailsTable = ( {
	accounts,
	setAccounts,
}: {
	accounts: BacsAccountDetails[];
	setAccounts: ( accounts: BacsAccountDetails[] ) => void;
} ) => {
	const [ selectedRows, setSelectedRows ] = useState< number[] >( [] );
	const [ draggedItem, setDraggedItem ] = useState< number | null >( null );
	const tableRef = useRef< HTMLTableElement >( null );

	const addAccount = () => {
		setAccounts( [
			...accounts,
			{
				account_name: '',
				account_number: '',
				bank_name: '',
				sort_code: '',
				iban: '',
				bic: '',
			},
		] );
	};

	const removeSelectedAccounts = () => {
		if ( selectedRows.length === 0 ) return;

		// Sort indices in descending order to avoid shifting issues
		const sortedIndices = [ ...selectedRows ].sort( ( a, b ) => b - a );
		const newAccounts = [ ...accounts ];

		sortedIndices.forEach( ( index ) => {
			newAccounts.splice( index, 1 );
		} );

		setAccounts( newAccounts );
		setSelectedRows( [] );
	};

	const updateAccount = (
		index: number,
		field: keyof BacsAccountDetails,
		value: string
	) => {
		const newAccounts = [ ...accounts ];
		newAccounts[ index ] = { ...newAccounts[ index ], [ field ]: value };
		setAccounts( newAccounts );
	};

	const toggleSelectRow = ( index: number ) => {
		if ( selectedRows.includes( index ) ) {
			setSelectedRows( selectedRows.filter( ( i ) => i !== index ) );
		} else {
			setSelectedRows( [ ...selectedRows, index ] );
		}
	};

	// Drag handle specific handlers
	const handleDragStart = (
		e: React.DragEvent< HTMLDivElement >,
		index: number
	) => {
		setDraggedItem( index );
		e.dataTransfer.effectAllowed = 'move';

		// Stop the event from propagating to the row
		e.stopPropagation();

		// Set data for drag operation
		e.dataTransfer.setData( 'text/plain', index.toString() );
	};

	const handleDragOver = ( e: React.DragEvent< HTMLTableRowElement > ) => {
		e.preventDefault();
	};

	const handleDragEnter = (
		e: React.DragEvent< HTMLTableRowElement >,
		index: number
	) => {
		e.preventDefault();
		if ( draggedItem === null || draggedItem === index ) return;

		const rows = tableRef.current?.querySelectorAll( 'tbody tr' );
		if ( ! rows ) return;

		rows[ index ].classList.add( 'drag-over' );
	};

	const handleDragLeave = (
		e: React.DragEvent< HTMLTableRowElement >,
		index: number
	) => {
		e.preventDefault();
		const rows = tableRef.current?.querySelectorAll( 'tbody tr' );
		if ( ! rows ) return;

		rows[ index ].classList.remove( 'drag-over' );
	};

	const handleDrop = (
		e: React.DragEvent< HTMLTableRowElement >,
		index: number
	) => {
		e.preventDefault();
		if ( draggedItem === null || draggedItem === index ) return;

		const rows = tableRef.current?.querySelectorAll( 'tbody tr' );
		if ( ! rows ) return;

		rows[ index ].classList.remove( 'drag-over' );

		// Reorder the accounts
		const newAccounts = [ ...accounts ];
		const [ movedItem ] = newAccounts.splice( draggedItem, 1 );
		newAccounts.splice( index, 0, movedItem );
		setAccounts( newAccounts );

		// Update selected rows indices
		setSelectedRows( ( prev ) => {
			const newSelectedRows = [ ...prev ];

			// Update the indices of selected rows after the reordering
			return newSelectedRows.map( ( rowIndex ) => {
				if ( rowIndex === draggedItem ) return index;
				if (
					draggedItem < index &&
					rowIndex > draggedItem &&
					rowIndex <= index
				) {
					return rowIndex - 1;
				}
				if (
					draggedItem > index &&
					rowIndex < draggedItem &&
					rowIndex >= index
				) {
					return rowIndex + 1;
				}
				return rowIndex;
			} );
		} );

		setDraggedItem( null );
	};

	const handleDragEnd = () => {
		setDraggedItem( null );
		const rows = tableRef.current?.querySelectorAll( 'tbody tr' );
		if ( ! rows ) return;

		rows.forEach( ( row ) => {
			row.classList.remove( 'drag-over' );
		} );
	};

	// Prevent the row selection when clicking on inputs
	const handleInputClick = ( e: React.MouseEvent ) => {
		e.stopPropagation();
	};

	return (
		<div className="bacs-account-details__table">
			<table className="widefat wc_input_table sortable" ref={ tableRef }>
				<thead>
					<tr>
						<th className="sort-handle"></th>
						<th>{ __( 'Account name', 'woocommerce' ) }</th>
						<th>{ __( 'Account no', 'woocommerce' ) }</th>
						<th>{ __( 'Bank name', 'woocommerce' ) }</th>
						<th>{ __( 'Routing no', 'woocommerce' ) }</th>
						<th>{ __( 'IBAN', 'woocommerce' ) }</th>
						<th>{ __( 'BIC/SWIFT', 'woocommerce' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ accounts.map( ( account, index ) => (
						<tr
							key={ index }
							onClick={ () => toggleSelectRow( index ) }
							onDragOver={ handleDragOver }
							onDragEnter={ ( e ) => handleDragEnter( e, index ) }
							onDragLeave={ ( e ) => handleDragLeave( e, index ) }
							onDrop={ ( e ) => handleDrop( e, index ) }
							className={
								selectedRows.includes( index ) ? 'selected' : ''
							}
						>
							<td className="sort-handle">
								<div
									className="drag-handle"
									draggable="true"
									onDragStart={ ( e ) =>
										handleDragStart( e, index )
									}
									onDragEnd={ handleDragEnd }
									onClick={ handleInputClick }
								>
									<Icon icon={ dragHandle } />
								</div>
							</td>
							<td>
								<TextControl
									value={ account.account_name }
									onChange={ ( value ) =>
										updateAccount(
											index,
											'account_name',
											value
										)
									}
									onClick={ handleInputClick }
								/>
							</td>
							<td>
								<TextControl
									value={ account.account_number }
									onChange={ ( value ) =>
										updateAccount(
											index,
											'account_number',
											value
										)
									}
									onClick={ handleInputClick }
								/>
							</td>
							<td>
								<TextControl
									value={ account.bank_name }
									onChange={ ( value ) =>
										updateAccount(
											index,
											'bank_name',
											value
										)
									}
									onClick={ handleInputClick }
								/>
							</td>
							<td>
								<TextControl
									value={ account.sort_code }
									onChange={ ( value ) =>
										updateAccount(
											index,
											'sort_code',
											value
										)
									}
									onClick={ handleInputClick }
								/>
							</td>
							<td>
								<TextControl
									value={ account.iban }
									onChange={ ( value ) =>
										updateAccount( index, 'iban', value )
									}
									onClick={ handleInputClick }
								/>
							</td>
							<td>
								<TextControl
									value={ account.bic }
									onChange={ ( value ) =>
										updateAccount( index, 'bic', value )
									}
									onClick={ handleInputClick }
								/>
							</td>
						</tr>
					) ) }
				</tbody>
				<tfoot>
					<tr>
						<td colSpan={ 7 }>
							<Button
								variant="secondary"
								className="add-account-button"
								onClick={ addAccount }
							>
								{ __( '+ Add account', 'woocommerce' ) }
							</Button>{ ' ' }
							<Button
								variant="secondary"
								className="remove-accounts-button"
								onClick={ removeSelectedAccounts }
								disabled={ selectedRows.length === 0 }
							>
								{ __(
									'Remove selected account(s)',
									'woocommerce'
								) }
							</Button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	);
};
