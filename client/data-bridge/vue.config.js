const TerserPlugin = require( 'terser-webpack-plugin' );

const filePrefix = 'data-bridge.';
const DEV_MODE = process.env.WEBPACK_TARGET === 'dev';

/**
 * In production libraries may be provided by ResourceLoader
 * to allow their caching across applications,
 * in dev it is still webpack's job to make them available
 */
function externals() {
	return DEV_MODE ? [] : [
		'vue',
		'vuex',
	];
}

module.exports = {
	configureWebpack: () => ( {
		output: {
			filename: process.env.VUE_CLI_MODERN_BUILD ? `${filePrefix}[name].modern.js` : `${filePrefix}[name].js`,
			libraryTarget: 'commonjs2',
			chunkFilename: process.env.VUE_CLI_MODERN_BUILD ? 'vendor-chunks.modern.js' : 'vendor-chunks.js',
		},
		entry: {
			app: './src/main.ts',
			init: './src/mediawiki/data-bridge.init.ts',
		},
		externals: externals(),
		optimization: {
			splitChunks: {
				minChunks: 2,
			},
			minimize: !DEV_MODE,
			minimizer: [ new TerserPlugin( {
				include: /\.js$/,
				sourceMap: true,
				extractComments: false,
			} ) ],
		},
	} ),
	chainWebpack: ( config ) => {
		if ( process.env.NODE_ENV === 'production' ) {
			config.plugin( 'extract-css' )
				.tap( ( [ options, ...args ] ) => [
					Object.assign( {}, options, { filename: `css/${filePrefix}[name].css` } ),
					...args,
				] );
		}
	},
	css: {
		loaderOptions: {
			sass: {
				prependData: '@import "@/presentation/styles/_main.scss";',
			},
		},
	},
	transpileDependencies: [
		'serialize-error',
	],
};
