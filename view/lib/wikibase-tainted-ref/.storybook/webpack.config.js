const path = require( 'path' );
const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );
const ourVueConfig = require( '../vue.config' );

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
				options: ourVueConfig.css.loaderOptions.sass,
			},
		],
	} );
	config.module.rules.push( {
		test: /\.vue$/,
		loader: 'storybook-addon-vue-info/loader',
		enforce: 'post',
	} );
	return config;
};
