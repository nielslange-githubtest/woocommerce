/**
 * External dependencies
 */
import { union } from 'lodash';
import { Reducer } from 'redux';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { Actions } from './actions';
import { SettingsState } from './types';

const reducer: Reducer< SettingsState, Actions > = ( state = {}, action ) => {
	switch ( action.type ) {
		case TYPES.SET_IS_REQUESTING:
			return {
				...state,
				[ action.group ]: {
					...state[ action.group ],
					isRequesting: action.isRequesting,
				},
			};
		case TYPES.UPDATE_ERROR_FOR_GROUP: {
			const { error, group, settings, time } = action;

			const settingsErrors = settings
				? Object.keys( settings ).reduce< Record< string, unknown > >(
						( acc, key ) => {
							acc[ key ] = error;
							return acc;
						},
						{}
				  )
				: {};

			return {
				...state,
				[ group ]: {
					settings: state[ group ]?.settings || {},
					settingsErrors,
					error,
					lastReceived: time,
				},
			};
		}
		case TYPES.UPDATE_SETTINGS_FOR_GROUP: {
			const { settings, group, time } = action;
			const settingsIds = settings ? Object.keys( settings ) : [];
			const groupState = state[ group ];
			const dirty = Array.isArray( groupState?.dirty )
				? union( groupState.dirty, settingsIds )
				: settingsIds;

			return {
				...state,
				[ group ]: {
					settings,
					error: null,
					lastReceived: time,
					dirty,
					isRequesting: groupState?.isRequesting || false,
				},
			};
		}
		case TYPES.CLEAR_IS_DIRTY:
			return {
				...state,
				[ action.group ]: {
					...state[ action.group ],
					dirty: [],
				},
			};
		case TYPES.CLEAR_SETTINGS:
			return {};
		default:
			return state;
	}
};

export type State = ReturnType< typeof reducer >;
export default reducer;
