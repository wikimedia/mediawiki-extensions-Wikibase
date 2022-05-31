const path = require( 'path' );
const HtmlWebpackPlugin = require( 'html-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

const outputPath = path.resolve( __dirname, '../../../docs/rest-api' ); // eslint-disable-line no-undef

module.exports = {
	mode: 'development',
	entry: {
		app: require.resolve( __dirname, 'index.js' ) // eslint-disable-line no-undef
	},
	resolve: {
		extensions: [ '.ts', '.js' ]
	},
	module: {
		rules: [
			{
				test: /openapi\.json$/,
				use: [
					{
						// eslint-disable-next-line no-undef
						loader: path.resolve( __dirname, 'loaders', 'openapi-loader.js' )
					}
				]
			},
			{
				test: /\.css$/,
				use: [
					{ loader: 'style-loader' },
					{ loader: 'css-loader' }
				]
			}
		]
	},
	plugins: [
		new CleanWebpackPlugin(),
		new CopyWebpackPlugin( {
			patterns: [
				{
					// Copy the Swagger OAuth2 redirect file to the project root;
					// that file handles the OAuth2 redirect after authenticating the end-user.
					from: 'node_modules/swagger-ui/dist/oauth2-redirect.html',
					to: outputPath
				}
			]
		} ),
		new HtmlWebpackPlugin( {
			template: path.resolve( __dirname, 'index.html' ) // eslint-disable-line no-undef
		} )
	],
	output: {
		filename: '[name].bundle.js',
		path: outputPath
	}
};
