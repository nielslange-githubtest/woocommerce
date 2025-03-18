/**
 * External dependencies
 */
import {
	apiFetch,
	dispatch as depreciatedDispatch,
} from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './constants';
import { updateSettingsForGroup, updateErrorForGroup } from './actions';
import { isRestApiError } from '../types';

// Can be removed in WP 5.9.
const dispatch =
	controls && controls.dispatch ? controls.dispatch : depreciatedDispatch;

// [class-wc-rest-setting-options-controller.php](https://github.com/woocommerce/woocommerce/blob/28926968bdcd2b504e16761a483388f85ee0c151/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-setting-options-controller.php#L158-L248)
type RawSetting = {
	id: string;
	group_id: string;
	label: string;
	description: string;
	tip?: string;
	type:
		| 'text'
		| 'email'
		| 'number'
		| 'color'
		| 'password'
		| 'textarea'
		| 'select'
		| 'multiselect'
		| 'radio'
		| 'image_width'
		| 'checkbox';
	default: unknown;
	value: unknown;
	options: {
		[ key: string ]: string;
	};
	placeholder?: string;
};

function resultsToSettings( settings: RawSetting[] ) {
	return settings.reduce< {
		[ id: string ]: unknown;
	} >( ( resource, setting ) => {
		resource[ setting.id ] = setting.value;
		return resource;
	}, {} );
}

/**
 * Get the settings for a given group.
 *
 * @param {string} group The group to get the settings for.
 * @return {Promise<void>} A promise that resolves when the settings are retrieved.
 * @throws {Error} If the settings cannot be retrieved.
 */
export function* getSettings( group: string ) {
	yield dispatch( STORE_NAME, 'setIsRequesting', group, true );

	try {
		const url = NAMESPACE + '/settings/' + group;
		const results: RawSetting[] = yield apiFetch( {
			path: url,
			method: 'GET',
		} );
		const settings = resultsToSettings( results );

		return updateSettingsForGroup( group, settings );
	} catch ( error ) {
		if ( error instanceof Error || isRestApiError( error ) ) {
			return updateErrorForGroup( group, null, error.message );
		}
		throw new Error( `Unexpected error ${ error }` );
	} finally {
		yield dispatch( STORE_NAME, 'setIsRequesting', group, false );
	}
}

export function* getSettingsForGroup( group: string ) {
	return getSettings( group );
}
