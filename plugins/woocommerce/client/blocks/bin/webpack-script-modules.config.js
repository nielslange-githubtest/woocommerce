/**
 * External dependencies
 */
const path = require( 'path' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const [
	,
	moduleConfig,
] = require( '@wordpress/scripts/config/webpack.config' );
const RemoveFilesPlugin = require( './remove-files-webpack-plugin' );

/**
 * Internal dependencies
 */
const { sharedOptimizationConfig } = require( './webpack-shared-config' );
const {
	getScriptModuleEntryPoints,
} = require( './script-module-entry-points' );

const entries = getScriptModuleEntryPoints(
	path.resolve( __dirname, '../assets/js' )
);

// These are modules that are not tied to a specific block, but are still needed to be built.
const manuallyDefinedModules = {
	// DO NOT add new blocks here, use viewScriptModule in block.json and implement AbstractInteractivityAPIBlock instead.
	'woocommerce/product-collection':
		'./assets/js/blocks/product-collection/frontend.ts',
	'woocommerce/product-filters':
		'./assets/js/blocks/product-filters/frontend.ts',
	'woocommerce/product-filter-active':
		'./assets/js/blocks/product-filters/inner-blocks/active-filters/frontend.ts',
	'woocommerce/product-filter-attribute':
		'./assets/js/blocks/product-filters/inner-blocks/attribute-filter/frontend.ts',
	'woocommerce/product-filter-checkbox-list':
		'./assets/js/blocks/product-filters/inner-blocks/checkbox-list/frontend.ts',
	'woocommerce/product-filter-chips':
		'./assets/js/blocks/product-filters/inner-blocks/chips/frontend.ts',
	'woocommerce/product-filter-price':
		'./assets/js/blocks/product-filters/inner-blocks/price-filter/frontend.ts',
	'woocommerce/product-filter-price-slider':
		'./assets/js/blocks/product-filters/inner-blocks/price-slider/frontend.ts',
	'woocommerce/product-filter-rating':
		'./assets/js/blocks/product-filters/inner-blocks/rating-filter/frontend.ts',
	'woocommerce/product-filter-removable-chips':
		'./assets/js/blocks/product-filters/inner-blocks/removable-chips/frontend.ts',
	'woocommerce/product-filter-status':
		'./assets/js/blocks/product-filters/inner-blocks/status-filter/frontend.ts',
	'woocommerce/accordion-group':
		'./assets/js/blocks/accordion/accordion-group/frontend.js',
	'woocommerce/add-to-cart-form':
		'./assets/js/blocks/product-elements/add-to-cart-form/frontend.ts',
	'woocommerce/add-to-cart-with-options':
		'./assets/js/blocks/add-to-cart-with-options/frontend.ts',
	'woocommerce/add-to-cart-with-options-grouped-product-selector':
		'./assets/js/blocks/add-to-cart-with-options/grouped-product-selector/frontend.ts',
	'woocommerce/add-to-cart-with-options-quantity-selector':
		'./assets/js/blocks/add-to-cart-with-options/quantity-selector/frontend.ts',
	'woocommerce/add-to-cart-with-options-variation-selector':
		'./assets/js/blocks/add-to-cart-with-options/variation-selector/frontend.ts',

	'@woocommerce/stores/woocommerce/cart':
		'./assets/js/base/stores/woocommerce/cart.ts',
	'@woocommerce/stores/store-notices':
		'./assets/js/base/stores/store-notices.ts',
};

module.exports = {
	...moduleConfig,
	entry: {
		...entries,
		...manuallyDefinedModules,
	},
	optimization: sharedOptimizationConfig,
	name: 'interactivity-blocks-modules',
	output: {
		...moduleConfig.output,
		devtoolNamespace: 'wc',
		path: path.resolve( __dirname, '../build/' ),
	},
	module: {
		rules: [
			...moduleConfig.module.rules.filter(
				( rule ) =>
					! rule.test.test( '.css' ) &&
					! rule.test.test( '.scss' ) &&
					! rule.test.test( '.sass' )
			),
			{
				test: /\.s?css$/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'postcss-loader',
					{
						loader: 'sass-loader',
						options: {
							sassOptions: {
								includePaths: [ 'assets/css/abstracts' ],
							},
							additionalData: ( content, loaderContext ) => {
								const { resourcePath, rootContext } =
									loaderContext;
								const relativePath = path.relative(
									rootContext,
									resourcePath
								);

								if (
									relativePath.startsWith(
										'assets/css/abstracts/'
									) ||
									relativePath.startsWith(
										'assets\\css\\abstracts\\'
									)
								) {
									return content;
								}

								return (
									'@use "sass:math";' +
									'@use "sass:string";' +
									'@use "sass:color";' +
									'@use "sass:map";' +
									'@import "_colors"; ' +
									'@import "_variables"; ' +
									'@import "_breakpoints"; ' +
									'@import "_mixins"; ' +
									content
								);
							},
						},
					},
				],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin( {
			filename: '[name].css',
		} ),
		new DependencyExtractionWebpackPlugin( {
			combineAssets: true,
			combinedOutputFile: './interactivity-blocks-frontend-assets.php',
			requestToExternalModule( request ) {
				if ( request.startsWith( '@woocommerce/stores/' ) ) {
					return `import ${ request }`;
				}
			},
		} ),
		// Remove JS files generated by MiniCssExtractPlugin.
		new RemoveFilesPlugin( './build/**/*style.js' ),
	],
};
