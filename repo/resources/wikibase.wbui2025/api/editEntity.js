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

/**
 * Parse a given input into a full data value.
 *
 * @param {string} propertyId
 * @param {string} input
 * @returns {Promise<Object|null>} A data value object (with "type" and "value" keys),
 * or null if the input could not be parsed successfully.
 */
const parseValue = async function ( propertyId, input ) {
	try {
		const { results } = await api.get( {
			action: 'wbparsevalue',
			property: propertyId,
			values: [ input ]
		} );
		return results.find( ( result ) => result.raw === input );
	} catch ( e ) {
		return null;
	}
};

module.exports = {
	updateStatements,
	renderSnakValueHtml,
	renderPropertyLinkHtml,
	parseValue
};
