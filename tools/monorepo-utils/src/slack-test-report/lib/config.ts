/**
 * External dependencies
 */
import fs from 'fs';

/**
 * Configuration interfaces for Slack test report notifications
 */

interface Route {
	channels: string[];
	checkType?: string;
	refName?: string;
	excludeDefaultChannel?: boolean;
}

export interface ReporterConfig {
	defaultChannel: string;
	routes: Route[];
}

/**
 * Matches a pattern that may contain wildcards against a string
 *
 * @param {string} pattern - Pattern that may contain * wildcards
 * @param {string} str     - String to match against
 * @return {boolean} Whether the string matches the pattern
 */
function matchPattern( pattern: string, str: string ): boolean {
	const regexPattern = pattern
		.replace( /\*/g, '.*' )
		.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )
		.replace( '\\.\\*', '.*' );
	return new RegExp( `^${ regexPattern }$` ).test( str );
}

/**
 * Loads and parses a config file
 *
 * @param {string} configPath - Path to the config file
 * @return {ReporterConfig} The parsed and validated config object
 * @throws {Error} If the config file cannot be read or is invalid
 */
export function loadConfig( configPath: string ): any {
	let rawData;
	try {
		rawData = fs.readFileSync( configPath, 'utf8' );
	} catch ( error ) {
		throw new Error( `Failed to read config file: ${ error.message }` );
	}

	let parsedData;
	try {
		parsedData = JSON.parse( rawData );
	} catch ( error ) {
		throw new Error( `Failed to parse config file: ${ error.message }` );
	}

	return parsedData;
}

/**
 * Parses a config object and validates it against the expected schema
 *
 * @param {string} rawConfig - raw config data
 * @return {ReporterConfig} The parsed and validated config object
 * @throws {Error} If the config file cannot be read or is invalid
 */
export function parseConfig( rawConfig: unknown ): ReporterConfig {
	if ( ! rawConfig || typeof rawConfig !== 'object' ) {
		throw new Error(
			`Failed to parse config file: config needs to be an Object`
		);
	}

	const parsedConfig = rawConfig as ReporterConfig;

	if (
		! parsedConfig.defaultChannel ||
		typeof parsedConfig.defaultChannel !== 'string'
	) {
		throw new Error(
			'Failed to parse config file: defaultChannel must be a non-empty string'
		);
	}

	if ( ! parsedConfig.routes ) {
		return { defaultChannel: parsedConfig.defaultChannel, routes: [] };
	}

	if ( ! Array.isArray( parsedConfig.routes ) ) {
		throw new Error(
			'Failed to parse config file: routes must be an array'
		);
	}

	for ( const route of parsedConfig.routes ) {
		if ( typeof route !== 'object' ) {
			throw new Error(
				`Failed to parse config file: route needs to be an Object`
			);
		}

		if (
			! route.channels ||
			! Array.isArray( route.channels ) ||
			! route.channels.every( ( channel ) => typeof channel === 'string' )
		) {
			throw new Error(
				'Failed to parse config file: channels must be an array of strings'
			);
		}

		if (
			( ! route.checkType || typeof route.checkType !== 'string' ) &&
			( ! route.refName || typeof route.refName !== 'string' )
		) {
			throw new Error(
				'Failed to parse config file: route must have at least one of checkType or refName as a non-empty string'
			);
		}

		if (
			'excludeDefaultChannel' in route &&
			typeof route.excludeDefaultChannel !== 'boolean'
		) {
			throw new Error(
				'Failed to parse config file: excludeDefaultChannel must be a boolean when present'
			);
		}

		// Set excludeDefaultChannel to false if not present
		if ( ! ( 'excludeDefaultChannel' in route ) ) {
			route.excludeDefaultChannel = false;
		}
	}

	return parsedConfig;
}

/**
 * Get channels for a specific ref or check name from the config
 *
 * @param {ReporterConfig} config    - The parsed config object
 * @param {string}         refName   - The name of the ref
 * @param {string}         checkName - The name of the check
 * @return {string[]} Array of channel IDs where the notification should be sent
 */
export function getConfiguredChannels(
	config: ReporterConfig | undefined,
	refName: string,
	checkName: string
): string[] {
	if ( ! config ) {
		throw new Error( 'Config must be provided to get configured channels' );
	}

	const channels = new Set< string >();
	let excludeDefaultChannel = false;

	// Helper function to process matching routes
	const processRoute = ( route: Route ) => {
		route.channels.forEach( ( channel ) => channels.add( channel ) );
		if ( route.excludeDefaultChannel ) {
			excludeDefaultChannel = true;
		}
	};

	for ( const route of config.routes ) {
		const matchesRef =
			'refName' in route && matchPattern( route.refName, refName );
		const matchesCheck =
			'checkType' in route && route.checkType === checkName;

		if ( matchesRef || matchesCheck ) {
			processRoute( route );
		}
	}

	// Include the default channel unless explicitly excluded
	if ( ! excludeDefaultChannel ) {
		channels.add( config.defaultChannel );
	}

	return Array.from( channels );
}
