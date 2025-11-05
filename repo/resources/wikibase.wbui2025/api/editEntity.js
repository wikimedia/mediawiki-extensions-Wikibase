const { api } = require( './api.js' );

/**
 * @param {string} entityId The ID of the entity to update
 * @param {Array} statements
 */
const updateStatements = async function ( entityId, statements ) {
	return api.postWithEditToken( api.assertCurrentUser( {
		action: 'wbeditentity',
		id: entityId,
		data: JSON.stringify( { claims: statements } )
	} ) ).then( ( response ) => response.entity.claims );
};

/**
 * @param {string} generate
 * @param {Object} dataValue
 * @param {string|null} propertyId
 */
const renderSnakValue = async function ( generate, dataValue, propertyId = null ) {
	const params = {
		action: 'wbformatvalue',
		generate: generate,
		datavalue: JSON.stringify( dataValue )
	};

	if ( propertyId ) {
		params.property = propertyId;
	}

	const fetchResult = await api.get( params );
	return fetchResult.result;
};

/**
 * @param {Object} dataValue
 * @param {string|null} propertyId
 */
const renderSnakValueHtml = async function ( dataValue, propertyId = null ) {
	return renderSnakValue( 'text/html; disposition=verbose-preview', dataValue, propertyId );
};

/**
 * @param {Object} dataValue
 * @param {string|null} propertyId
 */
const renderSnakValueText = async function ( dataValue, propertyId = null ) {
	return renderSnakValue( 'text/plain', dataValue, propertyId );
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
 * @param {string} input
 * @param {Object} parseOptions
 * @returns {Promise<Object|null>} A data value object (with "type" and "value" keys),
 * or null if the input could not be parsed successfully.
 */
const parseValue = async function ( input, parseOptions = {} ) {
	try {
		const { results } = await api.get( Object.assign( {
			action: 'wbparsevalue',
			values: [ input ]
		}, parseOptions ) );
		return results.find( ( result ) => result.raw === input );
	} catch ( e ) {
		return null;
	}
};

module.exports = {
	updateStatements,
	renderSnakValueHtml,
	renderSnakValueText,
	renderPropertyLinkHtml,
	parseValue
};
