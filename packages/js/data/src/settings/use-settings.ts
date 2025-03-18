/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from './';
import type { Settings } from './types';

/**
 * Custom hook to manage settings for a given group.
 *
 * @param {string}   group          - The group of settings to manage.
 * @param {string[]} [settingsKeys] - The keys of the settings to manage.
 * @return {Object} An object containing the settings, error, requesting status, and dirty status.
 */
export const useSettings: (
	group: string,
	settingsKeys?: string[]
) => {
	persistSettings: () => Promise< void >;
	updateAndPersistSettings: {
		( name: string, settings: Settings ): Promise< void >;
		( settings: Settings ): Promise< void >;
	};
	updateSettings: {
		( name: string, settings: Settings ): void;
		( settings: Settings ): void;
	};
	settingsError: boolean;
	isRequesting: boolean | undefined;
	isDirty: boolean;
} & Settings = ( group: string, settingsKeys: string[] = [] ) => {
	const { requestedSettings, settingsError, isRequesting, isDirty } =
		useSelect(
			( select ) => {
				const {
					getLastSettingsErrorForGroup,
					getSettingsForGroup,
					getIsDirty,
					isUpdateSettingsRequesting,
				} = select( store );

				return {
					requestedSettings: getSettingsForGroup(
						group,
						settingsKeys
					),
					settingsError: Boolean(
						getLastSettingsErrorForGroup( group )
					),
					isRequesting: isUpdateSettingsRequesting( group ),
					isDirty: getIsDirty( group, settingsKeys ),
				};
			},
			// Ignore lint error because we sort the settingsKeys array
			// eslint-disable-next-line react-hooks/exhaustive-deps
			[ group, ...settingsKeys.sort() ]
		);

	const {
		persistSettingsForGroup,
		updateAndPersistSettingsForGroup,
		updateSettingsForGroup,
	} = useDispatch( store );

	const updateSettings = useCallback(
		( nameOrSettings: string | Settings, maybeSettings?: Settings ) => {
			// Backwards compatibility for the old interface
			if ( typeof nameOrSettings === 'string' && maybeSettings ) {
				updateSettingsForGroup( group, {
					[ nameOrSettings ]: maybeSettings,
				} );
			} else {
				updateSettingsForGroup( group, nameOrSettings as Settings );
			}
		},
		[ group, updateSettingsForGroup ]
	);

	const persistSettings = useCallback( async () => {
		// this action would simply persist all settings marked as dirty in the
		// store state and then remove the dirty record in the isDirtyMap
		await persistSettingsForGroup( group );
	}, [ group, persistSettingsForGroup ] );

	const updateAndPersistSettings = useCallback(
		async (
			nameOrSettings: string | Settings,
			maybeSettings?: Settings
		) => {
			// Backwards compatibility for the old interface
			if ( typeof nameOrSettings === 'string' && maybeSettings ) {
				await updateAndPersistSettingsForGroup( group, {
					[ nameOrSettings ]: maybeSettings,
				} );
			} else {
				await updateAndPersistSettingsForGroup(
					group,
					nameOrSettings as Settings
				);
			}
		},
		[ group, updateAndPersistSettingsForGroup ]
	);

	return {
		settingsError,
		isRequesting,
		isDirty,
		...requestedSettings,
		persistSettings,
		updateAndPersistSettings,
		updateSettings,
	};
};
