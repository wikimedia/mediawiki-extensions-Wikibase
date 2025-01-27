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

async function getOrCreateBotUser() {
	const botUser = getExistingCredentials()[ PURPOSE_BOT ];
	if ( !botUser ) {
		return createUser( PURPOSE_BOT, [ 'bot' ] );
	}

	return action.getAnon().account( botUser.name, botUser.password );
}

// This user should only be used in AuthTest.js so that it can be blocked without interfering with other tests that run
// in parallel.
async function getOrCreateAuthTestUser() {
	const authTestUser = getExistingCredentials()[ PURPOSE_AUTH_TEST ];
	if ( !authTestUser ) {
		return createUser( PURPOSE_AUTH_TEST, [] );
	}

	return action.getAnon().account( authTestUser.name, authTestUser.password );
}

module.exports = { getOrCreateBotUser, getOrCreateAuthTestUser };
