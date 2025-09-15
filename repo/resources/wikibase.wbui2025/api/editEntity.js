const { updateStatementData } = require( '../store/statementsStore.js' );
const { api } = require( './api.js' );

/**
 * @param {string} entityId The ID of the entity to update
 * @param {string} propertyId The ID of the property
 * @param {Array} statements
 */
const updateStatements = async function ( entityId, propertyId, statements ) {
	return api.postWithEditToken( {
		action: 'wbeditentity',
		id: entityId,
		data: JSON.stringify( { claims: statements } )
	} ).then( ( response ) => {
		if ( propertyId in response.entity.claims ) {
			response.entity.claims[ propertyId ]
				.forEach( ( statement ) => updateStatementData( statement.id, statement ) );
		}
		return response.entity.claims[ propertyId ];
	} );
};

/**
 * @param {Object} dataValue
 */
const renderSnakValueHtml = async function ( dataValue ) {
	const fetchResult = await api.get( {
		action: 'wbformatvalue',
		generate: 'text/html; disposition=verbose-preview',
		datavalue: JSON.stringify( dataValue )
	} );
	if ( !fetchResult.result.startsWith( '<' ) ) {
		return '<p>' + fetchResult.result + '</p>';
	}
	return fetchResult.result;
};

module.exports = {
	updateStatements,
	renderSnakValueHtml
};
