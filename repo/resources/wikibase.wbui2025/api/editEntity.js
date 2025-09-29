const { api } = require( './api.js' );

/**
 * @param {string} entityId The ID of the entity to update
 * @param {Array} statements
 */
const updateStatements = async function ( entityId, statements ) {
	return api.postWithEditToken( {
		action: 'wbeditentity',
		id: entityId,
		data: JSON.stringify( { claims: statements } )
	} ).then( ( response ) => response.entity.claims );
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
	return fetchResult.result;
};

const renderPropertyLinkHtml = async function ( propertyId ) {
	const fetchResult = await api.get( {
		action: 'wbformatentities',
		generate: 'text/html',
		ids: [ propertyId ]
	} );
	return fetchResult.wbformatentities && fetchResult.wbformatentities[ propertyId ];
};

module.exports = {
	updateStatements,
	renderSnakValueHtml,
	renderPropertyLinkHtml
};
