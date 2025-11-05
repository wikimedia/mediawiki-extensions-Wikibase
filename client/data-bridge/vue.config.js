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
	];
}

module.exports = {
	publicPath: '.',
	productionSourceMap: false,
	configureWebpack: () => ( {
		output: {
			filename: `${filePrefix}[name].js`,
			libraryTarget: DEV_MODE ? undefined : 'commonjs2',
			chunkFilename: 'vendor-chunks.js',
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
				sourceMap: false,
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

		config.module
			.rule( 'vue' )
			.use( 'vue-loader' )
			.tap( ( options ) => {
				return {
					...options,
					compilerOptions: {
					},
				};
			} );
	},
	css: {
		loaderOptions: {
			sass: {
				additionalData: '@import "@/presentation/styles/_main.scss";',
			},
		},
	},
	transpileDependencies: [
		'serialize-error',
	],
};
