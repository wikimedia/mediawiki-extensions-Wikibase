'use strict';

const { action, utils } = require( 'api-testing' );
const fs = require( 'fs' );

const CREDENTIALS_FILE = __dirname + '/.test-user-credentials.json';

const PURPOSE_BOT = 'bot';
const PURPOSE_AUTH_TEST = 'auth-test';

function getExistingCredentials() {
	return fs.existsSync( CREDENTIALS_FILE ) ? JSON.parse( fs.readFileSync( CREDENTIALS_FILE, 'utf8' ) ) : {};
}

async function createUser( purpose, groups ) {
	const name = utils.title( `${purpose}-user-` );
	const password = utils.title( 'very-secret-' );

	const root = await action.root();
	await root.createAccount( { username: name, password } );
	await root.addGroups( name, groups );

	fs.writeFileSync(
		CREDENTIALS_FILE,
		JSON.stringify( {
			...getExistingCredentials(),
			[ purpose ]: { name, password }
		} )
	);

	const user = action.getAnon();
	await user.login( name, password );

	return user;
}

async function getOrCreateUser( purpose, groups = [] ) {
	const user = getExistingCredentials()[ purpose ];
	if ( !user ) {
		return createUser( purpose, groups );
	}

	try {
		return await action.getAnon().account( user.name, user.password );
	} catch ( e ) {
		if ( e.message && e.message.includes( 'Incorrect username or password entered' ) ) {
			fs.unlink( CREDENTIALS_FILE, ( error ) => {
				if ( error ) {
					throw error;
				}
			} );
			throw new Error(
				`Failed to log in the "${purpose}" user, likely due to outdated credentials.` +
				' The credentials file was now deleted, which should fix the issue. Please try again.'
			);
		}

		throw e; // The credentials weren't the problem, rethrow.
	}
}

function getOrCreateBotUser() {
	return getOrCreateUser( PURPOSE_BOT, [ 'bot' ] );
}

// This user should only be used in AuthTest.js so that it can be blocked without interfering with other tests that run
// in parallel.
function getOrCreateAuthTestUser() {
	return getOrCreateUser( PURPOSE_AUTH_TEST );
}

module.exports = { getOrCreateBotUser, getOrCreateAuthTestUser };
