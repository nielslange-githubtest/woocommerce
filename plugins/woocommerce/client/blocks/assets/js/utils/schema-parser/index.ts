/**
 * External dependencies
 */
import Ajv from 'ajv';
import addFormats from 'ajv-formats';
import addErrors from 'ajv-errors';

const ajv = new Ajv( {
	allErrors: true,
	$data: true,
	validateSchema: true,
	validateFormats: true,
	strictSchema: false,
	strict: false,
	messages: true,
} );

addFormats( ajv, {
	mode: 'fast',
	formats: [ 'date', 'time', 'email', 'uri' ],
	keywords: true,
} );
addErrors( ajv );

// Add type declaration for window.schemaParser
declare global {
	interface Window {
		schemaParser: typeof ajv;
	}
}

window.schemaParser = ajv;
