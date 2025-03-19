/**
 * External dependencies
 */
const path = require( 'path' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const [
	,
	moduleConfig,
] = require( '@wordpress/scripts/config/webpack.config' );

/**
 * Internal dependencies
 */
const { sharedOptimizationConfig } = require( './webpack-shared-config' );

module.exports = {
	...moduleConfig,
	optimization: sharedOptimizationConfig,
	name: 'interactivity-blocks-modules',
	output: {
		devtoolNamespace: 'wc',
		filename: '[name].js',
		library: {
			type: 'module',
		},
		path: path.resolve( __dirname, '../build/' ),
		asyncChunks: false,
		chunkFormat: 'module',
		environment: { module: true },
		module: true,
	},
	plugins: [
		new DependencyExtractionWebpackPlugin( {
			combineAssets: true,
			combinedOutputFile: './interactivity-blocks-frontend-assets.php',
			requestToExternalModule( request ) {
				if ( request.startsWith( '@woocommerce/stores/' ) ) {
					return `import ${ request }`;
				}
			},
		} ),
	],
};
