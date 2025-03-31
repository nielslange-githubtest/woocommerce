/**
 * Internal dependencies
 */
import { loadConfig, parseConfig } from '../config';

describe( 'loadConfigData', () => {
	it( 'should read config from file', () => {
		const loadedConfig = loadConfig(
			`${ __dirname }/notifications-config.json`
		);

		expect( loadedConfig ).toMatchObject( {
			routes: [
				{
					checkType: 'release-checks',
					channels: [ 'CHANNEL_ID_1' ],
				},
				{
					checkType: 'daily-checks',
					channels: [ 'CHANNEL_ID_2' ],
				},
				{
					refName: 'release/**',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
	} );
} );

describe( 'parseConfig', () => {
	it( 'should parse empty config', () => {
		const parsed = parseConfig( {} );
		expect( parsed ).toMatchObject( {} );
	} );

	it( 'should parse checkType rule', () => {
		const parsed = parseConfig( {
			routes: [
				{
					checkType: 'release-checks',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
		expect( parsed ).toMatchObject( {
			routes: [
				{
					checkType: 'release-checks',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
	} );

	it( 'should parse refName rule', () => {
		const parsed = parseConfig( {
			routes: [
				{
					refName: 'release/**',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
		expect( parsed ).toMatchObject( {
			routes: [
				{
					refName: 'release/**',
					channels: [ 'CHANNEL_ID_1' ],
					excludeDefaultChannel: true,
				},
			],
		} );
	} );
} );
