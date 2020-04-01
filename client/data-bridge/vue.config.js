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
	configureWebpack: () => ( {
		output: {
			filename: `${filePrefix}[name].js`,
		},
		entry: {
			app: './src/main.ts',
			init: './src/mediawiki/data-bridge.init.ts',
		},
		externals: externals(),
		optimization: {
			minimize: true,
			minimizer: [ new TerserPlugin( {
				include: /\.common\.js$/,
				sourceMap: true,
				extractComments: false,
			} ) ],
		},
	} ),
	chainWebpack: ( config ) => {
		config.optimization.delete( 'splitChunks' );

		if ( process.env.NODE_ENV === 'production' ) {
			config.plugin( 'extract-css' )
				.tap( ( [ options, ...args ] ) => [
					Object.assign( {}, options, { filename: `${filePrefix}[name].css` } ),
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
};
