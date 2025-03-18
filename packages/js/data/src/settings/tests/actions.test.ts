/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import type { APIFetchOptions } from '@wordpress/api-fetch';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../constants';
import {
	createTestRegistry,
	createTestSettings,
	createTestError,
} from './utils';
import { Settings } from '../types';

// Mock data-controls
jest.mock( '@wordpress/data-controls', () => ( {
	...jest.requireActual( '@wordpress/data-controls' ),
	apiFetch: jest.fn(),
} ) );

jest.mock( '@wordpress/deprecated', () => jest.fn() );

describe( 'settings actions', () => {
	let registry: ReturnType< typeof createTestRegistry >;
	let settings: Settings;

	beforeEach( () => {
		settings = createTestSettings();
		registry = createTestRegistry();

		( apiFetch as jest.Mock ).mockReset();
		( apiFetch as jest.Mock ).mockImplementation(
			( { path, method, data }: APIFetchOptions ) => {
				expect( path ).toContain(
					'/wc-analytics/settings/general/batch'
				);
				expect( method ).toBe( 'POST' );
				expect( data ).toEqual( {
					update: Object.entries( settings ).map(
						( [ key, value ] ) => ( {
							id: key,
							value,
						} )
					),
				} );
				return Promise.resolve();
			}
		);
	} );

	describe( 'updateSettingsForGroup', () => {
		it( 'should update settings for group', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', settings );

			expect(
				registry.select( STORE_NAME ).getSettings( 'general' )
			).toEqual( {
				...settings,
				general: {
					...settings,
				},
			} );
		} );

		it( 'should update settings for group with deprecated way', () => {
			registry.dispatch( STORE_NAME ).updateSettingsForGroup( 'general', {
				general: settings,
			} );

			expect(
				registry.select( STORE_NAME ).getSettings( 'general' )
			).toEqual( {
				...settings,
				general: {
					...settings,
				},
			} );

			expect( deprecated ).toHaveBeenCalled();
		} );
	} );

	describe( 'persistSettingsForGroup', () => {
		it( 'should persist settings when there are dirty keys', async () => {
			// First make the settings dirty
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', settings );

			await registry
				.dispatch( STORE_NAME )
				.persistSettingsForGroup( 'general' );

			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
			expect(
				registry.select( STORE_NAME ).getIsDirty( 'general' )
			).toBe( false );
			expect(
				registry
					.select( STORE_NAME )
					.isUpdateSettingsRequesting( 'general' )
			).toBe( false );
		} );

		it( 'should not persist settings when there are no dirty keys', async () => {
			await registry
				.dispatch( STORE_NAME )
				.persistSettingsForGroup( 'general' );

			expect( apiFetch ).not.toHaveBeenCalled();
			expect(
				registry
					.select( STORE_NAME )
					.isUpdateSettingsRequesting( 'general' )
			).toBe( false );
		} );
	} );

	describe( 'updateAndPersistSettingsForGroup', () => {
		it( 'should update and persist settings for group successfully', async () => {
			await registry
				.dispatch( STORE_NAME )
				.updateAndPersistSettingsForGroup( 'general', settings );

			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
			expect(
				registry.select( STORE_NAME ).getSettings( 'general' )
			).toEqual( {
				...settings,
				general: {
					...settings,
				},
			} );

			expect(
				registry.select( STORE_NAME ).getIsDirty( 'general' )
			).toBe( false );
		} );

		it( 'should update and persist settings for group with deprecated way', async () => {
			await registry
				.dispatch( STORE_NAME )
				.updateAndPersistSettingsForGroup( 'general', {
					general: settings,
				} );

			expect( apiFetch ).toHaveBeenCalledTimes( 1 );
			expect(
				registry.select( STORE_NAME ).getSettings( 'general' )
			).toEqual( {
				...settings,
				general: {
					...settings,
				},
			} );

			expect(
				registry.select( STORE_NAME ).getIsDirty( 'general' )
			).toBe( false );
		} );
	} );

	describe( 'clearSettings', () => {
		it( 'should clear settings', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', settings );

			registry.dispatch( STORE_NAME ).clearSettings();

			expect(
				registry.select( STORE_NAME ).getSettings( 'general' )
			).toEqual( {
				general: {},
			} );
		} );
	} );

	describe( 'updateErrorForGroup', () => {
		it( 'should update error for group', () => {
			const error = createTestError();
			registry
				.dispatch( STORE_NAME )
				.updateErrorForGroup( 'general', null, error );

			expect(
				registry
					.select( STORE_NAME )
					.getLastSettingsErrorForGroup( 'general' )
			).toBe( error );
		} );
	} );

	describe( 'setIsRequesting', () => {
		it( 'should set is requesting state', () => {
			registry.dispatch( STORE_NAME ).setIsRequesting( 'general', true );

			expect(
				registry
					.select( STORE_NAME )
					.isUpdateSettingsRequesting( 'general' )
			).toBe( true );
		} );
	} );

	describe( 'clearIsDirty', () => {
		it( 'should clear dirty state', () => {
			// First make the settings dirty
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', settings );

			expect(
				registry
					.select( STORE_NAME )
					.getIsDirty( 'general', [ 'setting1' ] )
			).toBe( true );

			// Clear dirty state
			registry.dispatch( STORE_NAME ).clearIsDirty( 'general' );

			expect(
				registry
					.select( STORE_NAME )
					.getIsDirty( 'general', [ 'setting1' ] )
			).toBe( false );
		} );
	} );
} );
