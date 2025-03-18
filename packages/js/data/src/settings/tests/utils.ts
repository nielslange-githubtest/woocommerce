/**
 * External dependencies
 */
import { createRegistry } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../constants';
import * as selectors from '../selectors';
import * as actions from '../actions';
import reducer from '../reducer';
import { Settings } from '../types';

export const createTestStore = ( registry = createRegistry() ) => {
	const store = registry.registerStore( STORE_NAME, {
		reducer,
		actions,
		controls,
		selectors,
	} );
	return store;
};

/**
 * Creates a test registry with the settings store registered.
 * Using any type because the registry type from @wordpress/data is not properly exported.
 */
export const createTestRegistry = () => {
	const registry = createRegistry();
	createTestStore( registry );
	return registry;
};

/**
 * Creates test settings data with the correct Settings type.
 */
export const createTestSettings = (): Settings => ( {
	setting1: 'value1',
	setting2: 'value2',
} );

/**
 * Creates a test error.
 */
export const createTestError = ( message = 'Test error' ) =>
	new Error( message );
