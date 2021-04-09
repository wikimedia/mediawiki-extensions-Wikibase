const path = require( 'path' );
const ourVueConfig = require( '../vue.config' );

module.exports = async ( { config } ) => {
	config.resolve.alias[ '@' ] = path.resolve( __dirname, '../src' );
	config.resolve.extensions.push( '.ts', '.tsx', '.vue', '.css', '.less', '.scss', '.sass', '.html' );
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
	return config;
};
