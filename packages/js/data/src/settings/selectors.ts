/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { SettingsState, SettingsGroupType } from './types';

/**
 * Retrieves the names of all settings groups.
 *
 * @param {Object} state State param added by wp.data.
 */
export const getSettingsGroupNames = ( state: SettingsState ) => {
	return Object.keys( state );
};

/**
 * Retrieves the settings for a given group.
 *
 * @param {Object} state State param added by wp.data.
 * @param {string} group The settings group.
 */
export const getSettings = < T extends string >(
	state: SettingsState,
	group: T
): SettingsGroupType< T > & { [ K in T ]: SettingsGroupType< T > } => {
	// Clone the settings object to avoid mutating the original state.
	const settings = {
		...( state[ group ]?.settings || {} ),
	};
	const clonedSettings = { ...settings };

	// Ensure backwards compatibility with old settings structure.
	Object.defineProperty( settings, group, {
		enumerable: true,
		configurable: true,
		get: () => {
			deprecated( 'getSettings(...)[ group ]', {
				since: '9.9',
				version: '10.2',
				alternative: 'getSettings(...)',
				plugin: 'woocommerce',
				hint: 'getSettings returns the settings object.',
			} );

			return clonedSettings;
		},
	} );

	return settings as SettingsGroupType< T > & {
		[ K in T ]: SettingsGroupType< T >;
	};
};

/**
 * Retrieves the dirty keys for a given group.
 *
 * @param {Object} state State param added by wp.data.
 * @param {string} group The settings group.
 */
export const getDirtyKeys = ( state: SettingsState, group: string ) => {
	return state[ group ]?.dirty || [];
};

/**
 * Checks if is dirty for given keys in a group. If keys is empty, it will return false.
 *
 * @param {Object}   state State param added by wp.data.
 * @param {string}   group The settings group.
 * @param {string[]} keys  The keys to check.
 */
export const getIsDirty = (
	state: SettingsState,
	group: string,
	keys: string[] = []
) => {
	const dirtyMap = getDirtyKeys( state, group );
	// if empty array bail
	if ( dirtyMap.length === 0 ) {
		return false;
	}
	// if at least one of the keys is in the dirty map then the state is dirty
	// meaning it hasn't been persisted.
	return keys.some( ( key ) => dirtyMap.includes( key ) );
};

/**
 * Retrieves a subset of settings for a given group.
 *
 * @param {Object}   state State param added by wp.data.
 * @param {string}   group The settings group.
 * @param {string[]} keys  The keys to retrieve.
 */
export const getSettingsForGroup = < T extends string >(
	state: SettingsState,
	group: T,
	keys: ( keyof SettingsGroupType< T > )[]
) => {
	const settings = getSettings( state, group );

	return keys.reduce< Partial< SettingsGroupType< T > > >(
		( accumulator, key ) => {
			accumulator[ key ] = settings[ key ];
			return accumulator;
		},
		{}
	);
};

/**
 * Checks if an update request is currently being made for a given group.
 *
 * @param {Object} state State param added by wp.data.
 * @param {string} group The settings group.
 */
export const isUpdateSettingsRequesting = (
	state: SettingsState,
	group: string
) => {
	if ( ! state[ group ] ) {
		return false;
	}

	return state[ group ].isRequesting;
};

/**
 * Retrieves a setting value from the setting store.
 *
 * @param {Object}   state                   State param added by wp.data.
 * @param {string}   group                   The settings group.
 * @param {string}   settingId               The identifier for the setting.
 * @param {*}        [fallback=false]        The value to use as a fallback
 *                                           if the setting is not in the
 *                                           state.
 * @param {Function} [filter=( val ) => val] A callback for filtering the
 *                                           value before it's returned.
 *                                           Receives both the found value
 *                                           (if it exists for the key) and
 *                                           the provided fallback arg.
 *
 * @return {*}  The value present in the settings state for the given settingId.
 */
export function getSetting< T extends never >( // Using never type as the return type is unknown due to fallback
	state: SettingsState,
	group: T,
	settingId: string,
	fallback: T = false as T,
	filter = (
		val: T,
		// eslint-disable-next-line @typescript-eslint/no-unused-vars -- _fallback in default filter is unused.
		_fallback: T
	) => val
) {
	if ( ! state[ group ] ) {
		return fallback;
	}

	if ( ! state[ group ].settings ) {
		return fallback;
	}

	const settings = getSettings( state, group );

	// For backwards compatibility, if the group is the same as the name, return the group.
	if ( group === settingId ) {
		// eslint-disable-next-line no-console
		console.warn(
			'[] The getSetting selector has changed to `getSetting( state, group, settingId )`. Please update your code to use the selector with the new signature. This will be deprecated in 10.2.'
		);
		return filter( settings[ settingId ] as T, fallback );
	}

	if ( ! Object.prototype.hasOwnProperty.call( settings, settingId ) ) {
		return fallback;
	}

	const value = settings[ settingId ];
	return filter( value as T, fallback );
}

/**
 * Retrieves the last settings error for a given group.
 *
 * @param {Object} state State param added by wp.data.
 * @param {string} group The settings group.
 */
export const getLastSettingsErrorForGroup = (
	state: SettingsState,
	group: string
) => {
	return state[ group ]?.error;
};

/**
 * Retrieves the settings error for a given group and id.
 * If no id is provided, it will return the error for the group.
 *
 * @param {Object} state State param added by wp.data.
 * @param {string} group The settings group.
 * @param {string} id    The setting id.
 */
export const getSettingsError = (
	state: SettingsState,
	group: string,
	id: string
) => {
	if ( ! state[ group ] ) {
		return false;
	}

	if ( ! id ) {
		return state[ group ].error || false;
	}

	if ( ! state[ group ].settingsErrors ) {
		return false;
	}

	return state[ group ].settingsErrors[ id ];
};
