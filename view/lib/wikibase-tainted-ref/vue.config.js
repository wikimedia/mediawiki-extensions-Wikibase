const path = require( 'path' );
const filePrefix = 'tainted-ref.';
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
			init: './src/tainted-ref.init.ts',
		},
		externals: externals(),
		resolve: {
			symlinks: false,
			alias: {
				'@': path.join( __dirname, 'src' ),
			},
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
			config.module
				.rule( 'images' )
				.test( /\.(png|jpe?g|gif|svg)(\?.*)?$/ )
				.use( 'url-loader' )
				.loader( 'url-loader' )
				.options( {
					limit: -1,
					name: '[path]/[name].[ext]',
				} );
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
