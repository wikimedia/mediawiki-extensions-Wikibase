'use strict';

const { Assertion, expect, util: utils } = require( 'chai' );
const util = require( 'util' );

function purple( str ) {
	return `\x1b[38;5;99m${str}`;
}

function normal( str ) {
	return `\x1b[0m${str}`;
}

function saveAndRestoreColor( str ) {
	return `\x1b7${str}\x1b8`;
}

function format( obj ) {
	const options = { depth: 4, colors: true };
	const formattedObj = util.inspect( obj, options );
	return normal( formattedObj );
}

Assertion.addChainableMethod(
	'status',
	function ( code ) { this.equals( code ); },
	function () {
		const response = utils.flag( this, 'object' );
		utils.flag( this, 'object', response.status );
		utils.flag( this, 'response', response );
		const formattedResponseBody = saveAndRestoreColor(
			`${purple( 'Response body:' )} ${format( response.body )}`
		);
		utils.flag( this, 'message', `\n${formattedResponseBody}\nInvalid status` );
	}
);

module.exports = { expect };
