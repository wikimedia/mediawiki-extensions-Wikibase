const { BannerPlugin } = require( 'webpack' );
const TerserPlugin = require( 'terser-webpack-plugin' );

const filePrefix = 'data-bridge.';
const DEV_MODE = process.env.WEBPACK_TARGET === 'dev';

/**
 * In production libraries may be provided by ResourceLoader
 * to allow their caching across applications,
 * in dev it is still webpack's job to make them available
 */
function externals() {
	if ( DEV_MODE ) {
		return [];
	}

	// get external packages from @wmde/lib-version-check config
	const package = require( './package.json' );
	return Object.keys( package.config.remoteVersion );
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
		plugins: [
			// /*@nomin*/ instructs ResourceLoader not to try to minify our resources –
			// especially important for the modern bundle (ES6 syntax not supported by RL).
			new BannerPlugin( {
				banner: '/*!/*@nomin*/', // /*! to avoid comment being removed later
				raw: true, // must contain *exactly* that string, see ResourceLoader::FILTER_NOMIN
			} ),
		],
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
						compatConfig: {
							MODE: 3,
						},
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
