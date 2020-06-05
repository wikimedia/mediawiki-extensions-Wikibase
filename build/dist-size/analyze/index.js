require( 'cross-fetch/polyfill' );
const ApolloClient = require( 'apollo-boost' ).default;
const fs = require( 'fs' ).promises;
const process = require( 'process' );
const getHistoryForFile = require( './getHistoryForFile' );

// TODO better token name, once available in Jenkins
// https://phabricator.wikimedia.org/T254319
const accessToken = process.env.COMPOSER_GITHUB_OAUTHTOKEN;
if ( !accessToken ) {
	console.error( 'Please configure a github access token' );
	process.exit( 1 );
}

const [ _node, script, outputDirectory, repoOwner, repoName, ...files ] = process.argv;
if ( !outputDirectory || !repoOwner || !repoName || !files.length ) {
	console.error( `Usage: ${script} OUTPUT GITHUB_REPO_OWNER GITHUB_REPO FILES...` );
	process.exit( 1 );
}

async function main() {
	const client = new ApolloClient( {
		uri: 'https://api.github.com/graphql',
		request: ( operation ) => {
			operation.setContext( {
				headers: {
					authorization: `Bearer ${accessToken}`,
				},
			} );
		},
	} );

	const data = {};
	await Promise.all( files.map(
		async ( filePath ) => {
			data[ filePath ] = await getHistoryForFile( client, repoOwner, repoName, filePath );
			console.info( `Analysed ${filePath}` );
		},
	) );

	await fs.mkdir( outputDirectory );
	await fs.mkdir( `${outputDirectory}/lib` );
	await Promise.all( [
		fs.writeFile( `${outputDirectory}/data.json`, JSON.stringify( data ) ),
		fs.copyFile( 'build/dist-size/web/index.html', `${outputDirectory}/index.html` ),
		fs.copyFile( 'build/dist-size/web/lib/main.js', `${outputDirectory}/lib/main.js` ),
		fs.copyFile( 'node_modules/plotly.js/dist/plotly.min.js', `${outputDirectory}/lib/plotly.js` ),
	] );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
