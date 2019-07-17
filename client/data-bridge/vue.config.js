const filePrefix = 'data-bridge.';

module.exports = {
	configureWebpack: () => ( {
		output: {
			filename: `${filePrefix}[name].js`,
		},
		entry: {
			app: './src/main.ts',
			init: './src/mediawiki/data-bridge.init.ts',
		},
		externals: {
			vue: {
				commonjs: 'vue2',
				commonjs2: 'vue2',
				amd: 'vue2',
				root: 'vue2',
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
		}
	},
};
