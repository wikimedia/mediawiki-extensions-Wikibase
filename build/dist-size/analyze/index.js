'use strict';

require( 'cross-fetch/polyfill' );
const ApolloClient = require( 'apollo-boost' ).default;
const crypto = require( 'crypto' );
const { promises: fs, createWriteStream } = require( 'fs' );
const https = require( 'https' );
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

function download( url, path, expectedSHA256 ) {
	return new Promise( ( resolve, reject ) => {
		const stream = createWriteStream( path );
		const hash = crypto.createHash( 'sha256' );
		https.get(
			url,
			{ headers: { 'User-Agent': 'Wikibase-dist-size-analyze' } },
			( res ) => {
				res.pipe( stream );
				res.pipe( hash );
			},
		);
		stream.on( 'finish', () => {
			const actualSHA256 = hash.digest( 'hex' );
			if ( actualSHA256 === expectedSHA256 ) {
				resolve();
			} else {
				reject( `Download of ${url} has wrong SHA-256 hash: expected ${expectedSHA256}, actual ${actualSHA256}` );
			}
		} );
		stream.on( 'error', reject );
	} );
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
		download(
			'https://tools-static.wmflabs.org/cdnjs/ajax/libs/plotly.js/1.58.5/plotly.min.js',
			`${outputDirectory}/lib/plotly.js`,
			'7085d5a3331da1f63d752ddbfbcae92f46134b3296d46aa6364c5f13b87ff27c',
		),
	] );
}

main().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
