'use strict';

const { action, utils } = require( 'api-testing' );
const fs = require( 'fs' );

const CREDENTIALS_FILE = __dirname + '/.bot-credentials.json';

async function createBotUser() {
	const name = utils.title( 'r2d2' );
	const password = utils.title( 'very-secret-' );

	const root = await action.root();
	await root.createAccount( { username: name, password } );
	await root.addGroups( name, [ 'bot' ] );

	fs.writeFileSync( CREDENTIALS_FILE, JSON.stringify( { name, password } ) );

	await root.login( name, password ); // root is now the bot

	return root;
}

async function getOrCreateBotUser() {
	if ( !fs.existsSync( CREDENTIALS_FILE ) ) {
		return createBotUser();
	}

	const credentials = JSON.parse( fs.readFileSync( CREDENTIALS_FILE, 'utf8' ) );
	return action.getAnon().account( credentials.name, credentials.password );
}

module.exports = { getOrCreateBotUser };
