const path = require( 'path' );
const webpack = require( 'webpack' );
const HtmlWebpackPlugin = require( 'html-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

const outputPath = path.resolve( __dirname, '../../../docs/rest-api' ); // eslint-disable-line no-undef

module.exports = ( env, argv ) => {
	const isDevelopment = argv.mode === 'development';

	/* eslint-disable no-undef */
	// The wiki whose OpenAPI document the built page shows. Defaults to
	// Wikidata; wikis not deployed by Wikimedia set OPENAPI_DOC_HOST when
	// building. An empty host produces a server-relative URL for docs hosted
	// on the wiki's own origin, which also needs no CORS configuration.
	const openApiDocUrl = `${process.env.OPENAPI_DOC_HOST ?? 'https://www.wikidata.org'}/w/rest.php/wikibase/v1/openapi.json`;

	// In development the page shows the local wiki's document instead.
	// MW_SERVER/MW_SCRIPT_PATH follow the convention of the browser tests,
	// so mwcli development containers need no configuration at all.
	const devServer = process.env.MW_SERVER ?? 'http://default.mediawiki.local.wmftest.net:8080';
	const devScriptPath = process.env.MW_SCRIPT_PATH ?? '/w';
	/* eslint-enable no-undef */

	return {
		mode: argv.mode || 'production',
		entry: {
			app: require.resolve( __dirname, 'index.js' ) // eslint-disable-line no-undef
		},
		resolve: {
			extensions: [ '.ts', '.js' ]
		},
		module: {
			rules: [
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
			} ),
			new webpack.DefinePlugin( {
				// In development the page fetches through the dev server proxy
				// below, since wikis send no CORS headers by default.
				'process.env.OPENAPI_DOC_URL': JSON.stringify(
					isDevelopment ? '/openapi.json' : openApiDocUrl
				)
			} )
		],
		output: {
			filename: '[name].bundle.js',
			path: outputPath
		},
		devServer: {
			static: {
				directory: __dirname,
			},
			compress: true,
			port: 7000,
			proxy: {
				'/openapi.json': {
					target: devServer,
					pathRewrite: { '^/openapi.json': `${devScriptPath}/rest.php/wikibase/v1/openapi.json` },
					changeOrigin: true,
				},
			},
		},
	};
};
