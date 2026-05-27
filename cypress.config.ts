import { defineConfig } from 'cypress';
import { unlinkSync, existsSync } from 'fs';

const envLogDir = process.env.LOG_DIR ? process.env.LOG_DIR + '/Wikibase' : null;

if ( process.env.MW_SERVER === undefined || process.env.MW_SCRIPT_PATH === undefined ||
     process.env.MEDIAWIKI_USER === undefined || process.env.MEDIAWIKI_PASSWORD === undefined ) {
	throw new Error( 'Please define MW_SERVER, MW_SCRIPT_PATH, ' +
		'MEDIAWIKI_USER and MEDIAWIKI_PASSWORD environment variables' );
}
process.env.REST_BASE_URL = process.env.MW_SERVER + process.env.MW_SCRIPT_PATH + '/';

import { mwApiCommands } from 'cypress-wikibase-api';
import { clientFactory } from 'api-testing';

const compressedVideoPath = function ( uncompressedPath: string ): string {
	const lastDot = uncompressedPath.lastIndexOf( '.' );
	const base = uncompressedPath.slice( 0, lastDot );
	const ext = uncompressedPath.slice( lastDot );
	return base + '-compressed' + ext;
};

export default defineConfig( {
	e2e: {
		supportFile: 'cypress/support/e2e.ts',
		baseUrl: process.env.MW_SERVER + process.env.MW_SCRIPT_PATH,
		mediawikiAdminUsername: process.env.MEDIAWIKI_USER,
		mediawikiAdminPassword: process.env.MEDIAWIKI_PASSWORD,
		wikibasePropertyIds: {
			string: process.env.WIKIBASE_PROPERTY_STRING,
		},
		setupNodeEvents( on, config ) {
			on( 'task', {
				/* eslint-disable no-console */
				log( message ) {
					console.log( message );
					return null;
				},
				table( message ) {
					console.table( message );
					return null;
				},
				/* eslint-enable */
				// TODO: T427277 (follow-up) we might want to add this cypress task implementation to 'cypress-wikibase-api` library and call it from the test directly.
				async 'MwApi:DeletePage'( { title }: { title: string } ) {
					const client = clientFactory.getActionClient( null );
					await client.login( process.env.MEDIAWIKI_USER, process.env.MEDIAWIKI_PASSWORD );
					await client.loadTokens( [ 'csrf' ] );
					await client.action( 'delete', {
						title,
						reason: 'Cypress test cleanup',
						token: await client.token( 'csrf' ),
					}, 'POST' );
					return null;
				},
				// eslint-disable-next-line es-x/no-rest-spread-properties
				...mwApiCommands( config ),
			} );
			on( 'after:spec', ( spec, results ) => {
				if ( results && results.video ) {
					// Do we have failures for any retry attempts?
					const failures = results.tests.some(
						( test ) => test.attempts
							.some( ( attempt ) => attempt.state === 'failed' ),
					);
					if ( !failures ) {
						// delete the video if the spec passed and no tests retried
						/* eslint-disable security/detect-non-literal-fs-filename */
						if ( existsSync( results.video ) ) {
							unlinkSync( results.video );
						}
						// Cypress creates a zero-byte "compressed" video file even if you disable compression.
						// Delete that file
						if ( existsSync( results.video ) ) {
							unlinkSync( compressedVideoPath( results.video ) );
						}
						/* eslint-enable security/detect-non-literal-fs-filename */
					}
				}
			} );
		},
		defaultCommandTimeout: 20000,
	},
	screenshotsFolder: envLogDir || 'cypress/screenshots',
	video: true,
	videoCompression: false,
	videosFolder: envLogDir || 'cypress/videos',
	downloadsFolder: envLogDir || 'cypress/downloads',
} );
