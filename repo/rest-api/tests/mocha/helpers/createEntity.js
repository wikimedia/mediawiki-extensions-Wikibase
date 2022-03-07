'use strict';

const { action } = require( 'api-testing' );

module.exports = async function createEntity( type, entity ) {
	return action.getAnon().action( 'wbeditentity', {
		new: type,
		token: '+\\',
		data: JSON.stringify( entity )
	}, 'POST' );
};
