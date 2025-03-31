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

	// todo: validate defaultChannel exists and is a string

	if ( ! parsedConfig.routes || ! Array.isArray( parsedConfig.routes ) ) {
		return { parsedConfig.defaultChannel, routes: [] };
	}

	for ( const rule of parsedConfig.routes ) {
		if ( typeof rule !== 'object' ) {
			throw new Error(
				`Failed to parse config file: rule needs to be an Object`
			);
		}

		// todo: validate channels exists and is an array of strings

		// todo:  validate the rule has at least one of checkType or refName

		// todo: if excludeDefaultChannel is present, validate it is a boolean

		// todo: if excludeDefaultChannel is present, add is as false
	}

	return parsedConfig;
}

/**
 * Get channels for a specific ref or check name from the config
 *
 * @param {ReporterConfig} config         - The parsed config object
 * @param {string}         refType        - The type of ref (e.g., 'branch', 'tag')
 * @param {string}         refName        - The name of the ref
 * @param {string}         checkName      - The name of the check
 * @param {string}         defaultChannel - The default channel to use if no specific config
 * @return {string[]} Array of channel IDs where the notification should be sent
 */
export function getConfiguredChannels(
	config: ReporterConfig | undefined,
	refType: string,
	refName: string,
	checkName: string,
	defaultChannel: string
): string[] {
	if ( ! config ) {
		return [ defaultChannel ];
	}

	const channels = new Set< string >();
	let excludeDefaultChannel = false;

	for ( const rule of config.rules ) {
		// Check ref-specific rules
		if ( 'refName' in rule && matchPattern( rule.refName, refName ) ) {
			rule.channels.forEach( ( channel ) => channels.add( channel ) );
			if ( rule.excludeDefaultChannel ) {
				excludeDefaultChannel = true;
			}
		}

		// Check checkType-specific rules
		if ( 'checkType' in rule && rule.checkType === checkName ) {
			rule.channels.forEach( ( channel ) => channels.add( channel ) );
		}
	}

	// If no specific channels were configured or excludeDefaultChannel is false,
	// include the default channel
	if ( ! excludeDefaultChannel && channels.size === 0 ) {
		channels.add( defaultChannel );
	}

	return Array.from( channels );
}
