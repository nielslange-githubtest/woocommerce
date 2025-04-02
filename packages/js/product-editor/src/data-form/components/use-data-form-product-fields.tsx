/**
 * External dependencies
 */
import type { ComponentType } from 'react';
import { useMemo, createElement } from '@wordpress/element';
import { Template, TemplateArray } from '@wordpress/blocks';
import { Field, DataFormControlProps } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getProductField } from './fields';
import { ProductDataFormControlProps } from './fields/types';

/**
 * Get the property key for a field definition
 *
 * @param field - The field definition
 * @return The key for the field
 */
function getFieldKey( field: Template ): string {
	const attributes = field[ 1 ] || {};
	// We support the block binding structure.
	if (
		attributes.metadata?.bindings?.value?.source ===
			'woocommerce/entity-product' &&
		attributes.metadata?.bindings?.value?.args?.prop
	) {
		return attributes.metadata?.bindings?.value?.args?.prop;
	} else if ( attributes.name ) {
		return attributes.name;
	}
	return field[ 0 ];
}

function addAttributesToEdit(
	Edit: string | ComponentType< ProductDataFormControlProps >,
	attributes: Record< string, unknown >
): string | ComponentType< DataFormControlProps< Product > > {
	if ( typeof Edit === 'string' ) {
		return Edit;
	}
	return function EditWithAttributes( props ) {
		return <Edit { ...props } attributes={ attributes } />;
	};
}
/**
 * Hook that transforms field definitions into DataForm compatible field objects.
 * Each field definition is an array where:
 * - First item is the field name (matching a block definition)
 * - Second item is an object with field parameters
 *
 * @param fields - Array of field definitions
 * @return Array of DataForm compatible field objects
 */
export function useDataFormProductFields(
	fields: TemplateArray = []
): Field< Product >[] {
	return useMemo( () => {
		return fields.map( ( [ fieldName, params ] ) => {
			const fieldDefinition = getProductField( fieldName );
			// Convert the field definition to a DataForm field format
			const field: Field< Product > = {
				...fieldDefinition,
				Edit:
					fieldDefinition?.Edit && params
						? addAttributesToEdit( fieldDefinition.Edit, params )
						: fieldDefinition?.Edit,
				id: getFieldKey( [ fieldName, params ] ),
			};

			return field;
		} );
	}, [ fields ] );
}
