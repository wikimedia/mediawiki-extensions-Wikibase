const path = require( 'path' );
const ourVueConfig = require( '../vue.config' );
const ourPostCssConfig = require( '../postcss.config' );

module.exports = async ( { config } ) => {
	config.resolve.alias[ '@' ] = path.resolve( __dirname, '../src' );
	config.module.rules.push( {
		test: /\.scss$/,
		use: [
			'vue-style-loader',
			'css-loader',
			{
				loader: 'postcss-loader',
				options: {
					postcssOptions: {
						ident: 'postcss',
						plugins: ourPostCssConfig.plugins,
					},
				},
			},
			{
				loader: 'sass-loader',
				options: ourVueConfig.css.loaderOptions.sass,
			},
		],
	} );
	return config;
};
