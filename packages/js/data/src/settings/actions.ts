/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { apiFetch, select } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { DispatchFromMap } from '@automattic/data-stores';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { NAMESPACE } from '../constants';
import { STORE_NAME } from './constants';
import TYPES from './action-types';
import { Settings } from './types';

// Can be removed in WP 5.9, wp.data is supported in >5.7.
const resolveSelect =
	controls && controls.resolveSelect ? controls.resolveSelect : select;

/**
 * Check if the settings are in the legacy format to maintain backwards compatibility.
 */
const isLegacyGroupSetting = ( group: string, settings: Settings ) => {
	return (
		Object.keys( settings ).length === 1 &&
		Object.keys( settings )[ 0 ] === group
	);
};

/**
 * Update the settings for a given group. This only updates the settings in the
 * state, it does not persist them to the database.
 *
 * @param {string}   group    The group to update.
 * @param {Settings} settings The settings to update.
 * @param {Date}     [time]   The time to update the settings.
 */
export function updateSettingsForGroup(
	group: string,
	settings: Settings,
	time = new Date()
) {
	let settingsToUpdate = {
		...settings,
	};

	if ( isLegacyGroupSetting( group, settings ) ) {
		deprecated(
			'updateSettingsForGroup settings argument with group name as key',
			{
				since: '9.9',
				version: '10.2',
				alternative: 'settings object directly',
				plugin: 'woocommerce',
				hint: 'The method should be updated to use the new signature.',
			}
		);
		settingsToUpdate = settings[ group ] as Settings;
	}

	return {
		type: TYPES.UPDATE_SETTINGS_FOR_GROUP,
		group,
		settings: settingsToUpdate,
		time,
	};
}

/**
 * Update the error for a given group.
 *
 * @param {string}          group    The group to update.
 * @param {Settings | null} settings The settings to update.
 * @param {unknown}         error    The error to update.
 * @param {Date}            [time]   The time to update the error.
 */
export function updateErrorForGroup(
	group: string,
	settings: Settings | null,
	error: unknown,
	time = new Date()
) {
	return {
		type: TYPES.UPDATE_ERROR_FOR_GROUP,
		group,
		settings,
		error,
		time,
	};
}

/**
 * Set the is requesting flag for a given group.
 *
 * @param {string}  group        The group to update.
 * @param {boolean} isRequesting The is requesting flag to update.
 */
export function setIsRequesting( group: string, isRequesting: boolean ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		group,
		isRequesting,
	};
}

export function clearIsDirty( group: string ) {
	return {
		type: TYPES.CLEAR_IS_DIRTY,
		group,
	};
}

/**
 * Persist the settings for a given group.
 *
 * @param {string} group The group to persist.
 */
export function* persistSettingsForGroup( group: string ) {
	// first dispatch the is persisting action
	yield setIsRequesting( group, true );
	// get all dirty keys with select control
	const dirtyKeys: string[] = yield resolveSelect(
		STORE_NAME,
		'getDirtyKeys',
		group
	);

	// if there is nothing dirty, bail
	if ( dirtyKeys.length === 0 ) {
		yield setIsRequesting( group, false );
		return;
	}

	const dirtyData: Record< string, unknown > = yield resolveSelect(
		STORE_NAME,
		'getSettingsForGroup',
		group,
		dirtyKeys
	);

	const update = Object.entries( dirtyData ).map( ( [ key, value ] ) => {
		return {
			id: key,
			value,
		};
	} );

	try {
		const results: unknown = yield apiFetch( {
			path: `${ NAMESPACE }/settings/${ group }/batch`,
			method: 'POST',
			data: { update },
		} );

		if ( ! results ) {
			throw new Error(
				__(
					'There was a problem updating your settings.',
					'woocommerce'
				)
			);
		}

		// remove dirtyKeys from map - note we're only doing this if there is no error.
		yield clearIsDirty( group );
	} catch ( e ) {
		yield updateErrorForGroup( group, dirtyData, e );
		throw e;
	} finally {
		yield setIsRequesting( group, false );
	}
}

/**
 * Update the settings for a given group and persist them immediately.
 *
 * @param {string}   group    The group to update.
 * @param {Settings} settings The settings to update.
 */
export function* updateAndPersistSettingsForGroup(
	group: string,
	settings: Settings
) {
	let settingsToUpdate = {
		...settings,
	};
	if ( isLegacyGroupSetting( group, settings ) ) {
		deprecated(
			'updateAndPersistSettingsForGroup settings argument with group name as key',
			{
				since: '9.9',
				version: '10.2',
				alternative: 'settings object directly',
				plugin: 'woocommerce',
				hint: 'The method should be updated to use the new signature.',
			}
		);

		settingsToUpdate = settings[ group ] as Settings;
	}

	// Preemptively set requesting to allow for loading UI when optimistically updating settings.
	yield setIsRequesting( group, true );
	yield updateSettingsForGroup( group, settingsToUpdate );
	yield* persistSettingsForGroup( group );
}

/**
 * Clear the settings
 */
export function clearSettings() {
	return {
		type: TYPES.CLEAR_SETTINGS,
	};
}

export type Actions = ReturnType<
	| typeof updateSettingsForGroup
	| typeof updateErrorForGroup
	| typeof setIsRequesting
	| typeof clearIsDirty
	| typeof clearSettings
>;

export type ActionDispatchers = DispatchFromMap< {
	createProduct: typeof persistSettingsForGroup;
	updateProduct: typeof updateAndPersistSettingsForGroup;
} >;
