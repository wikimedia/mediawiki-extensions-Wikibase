const path = require( 'path' );
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );

module.exports = async ( { config } ) => {
	config.resolve.alias[ '@' ] = path.resolve( __dirname, '../src' );
	config.resolve.extensions.push( '.ts', '.tsx', '.vue', '.css', '.less', '.scss', '.sass', '.html' );
	config.module.rules.push( {
		test: /\.ts$/,
		exclude: /node_modules/,
		use: [
			{
				loader: 'ts-loader',
				options: {
					appendTsSuffixTo: [ /\.vue$/ ],
					transpileOnly: true,
				},
			},
		],
	} );
	config.plugins.push( new ForkTsCheckerWebpackPlugin() );
	config.module.rules.push( {
		test: /\.scss$/,
		use: [
			'vue-style-loader',
			'css-loader',
			{
				loader: 'sass-loader',
			},
		],
	} );
	config.module.rules.push( {
		test: /\/stories\/.+\.js$/,
		loaders: [ require.resolve( '@storybook/addon-storysource/loader' ) ],
		enforce: 'pre',
	} );
	return config;
};
