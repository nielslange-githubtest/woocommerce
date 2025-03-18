/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../constants';
import { Settings } from '../types';
import { createTestRegistry, createTestSettings } from './utils';

jest.mock( '@wordpress/deprecated', () => jest.fn() );

describe( 'settings selectors', () => {
	let registry: ReturnType< typeof createTestRegistry >;
	let settings: Settings;

	beforeEach( () => {
		registry = createTestRegistry();
		settings = createTestSettings();
		registry
			.dispatch( STORE_NAME )
			.updateSettingsForGroup( 'general', settings );
	} );

	describe( 'getSettingsGroupNames', () => {
		it( 'should get settings group names', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'tax', settings );

			expect(
				registry.select( STORE_NAME ).getSettingsGroupNames()
			).toEqual( [ 'general', 'tax' ] );
		} );
	} );

	describe( 'getSettings', () => {
		it( 'should get settings for group', () => {
			const generalSettings = registry
				.select( STORE_NAME )
				.getSettings( 'general' );

			expect( generalSettings ).toEqual( {
				...settings,
				general: {
					...settings,
				},
			} );

			expect( generalSettings.general ).toEqual( settings );
			expect( deprecated ).toHaveBeenCalled();
		} );
	} );

	describe( 'getSettingsForGroup', () => {
		it( 'should get specific settings for group', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSettingsForGroup( 'general', [ 'setting1' ] )
			).toEqual( {
				setting1: 'value1',
			} );
		} );

		it( 'should return empty object when group does not exist', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSettingsForGroup( 'nonexistent', [ 'setting1' ] )
			).toEqual( {} );
		} );

		it( 'should return empty object when empty keys array provided', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSettingsForGroup( 'general', [] )
			).toEqual( {} );
		} );
	} );

	describe( 'getSetting', () => {
		it( 'should get single setting with fallback', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSetting( 'general', 'setting1', 'fallback' )
			).toBe( 'value1' );
		} );

		it( 'should return fallback when setting does not exist', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSetting( 'general', 'nonexistent', 'fallback' )
			).toBe( 'fallback' );
		} );

		it( 'should return fallback when group does not exist', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getSetting( 'nonexistent', 'setting1', 'fallback' )
			).toBe( 'fallback' );
		} );
	} );

	describe( 'getDirtyKeys', () => {
		beforeEach( () => {
			registry.dispatch( STORE_NAME ).clearSettings();
		} );

		it( 'should get dirty keys', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', { setting1: 'new value' } );
			expect(
				registry.select( STORE_NAME ).getDirtyKeys( 'general' )
			).toContain( 'setting1' );
		} );

		it( 'should return empty array when no dirty keys exist', () => {
			expect(
				registry.select( STORE_NAME ).getDirtyKeys( 'general' )
			).toEqual( [] );
		} );
	} );

	describe( 'getIsDirty', () => {
		beforeEach( () => {
			registry.dispatch( STORE_NAME ).clearSettings();
		} );

		it( 'should return true when settings are dirty', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', { setting1: 'new value' } );

			expect(
				registry
					.select( STORE_NAME )
					.getIsDirty( 'general', [ 'setting1' ] )
			).toBe( true );
		} );

		it( 'should return false when settings are not dirty', () => {
			registry
				.dispatch( STORE_NAME )
				.updateSettingsForGroup( 'general', { setting1: 'new value' } );

			expect(
				registry
					.select( STORE_NAME )
					.getIsDirty( 'general', [ 'setting2' ] )
			).toBe( false );
		} );

		it( 'should return true if any of the provided keys are dirty', () => {
			registry.dispatch( STORE_NAME ).updateSettingsForGroup( 'general', {
				setting1: 'new value',
				setting2: 'new value',
			} );

			expect(
				registry
					.select( STORE_NAME )
					.getIsDirty( 'general', [ 'setting1', 'setting3' ] )
			).toBe( true );
		} );
	} );

	describe( 'getLastSettingsErrorForGroup', () => {
		it( 'should return null when no error exists', () => {
			expect(
				registry
					.select( STORE_NAME )
					.getLastSettingsErrorForGroup( 'general' )
			).toBeNull();
		} );

		it( 'should return error when it exists', () => {
			const error = new Error( 'Test error' );
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

	describe( 'isUpdateSettingsRequesting', () => {
		it( 'should return false by default', () => {
			expect(
				registry
					.select( STORE_NAME )
					.isUpdateSettingsRequesting( 'general' )
			).toBe( false );
		} );

		it( 'should return true when settings are being updated', () => {
			registry.dispatch( STORE_NAME ).setIsRequesting( 'general', true );

			expect(
				registry
					.select( STORE_NAME )
					.isUpdateSettingsRequesting( 'general' )
			).toBe( true );
		} );
	} );
} );
