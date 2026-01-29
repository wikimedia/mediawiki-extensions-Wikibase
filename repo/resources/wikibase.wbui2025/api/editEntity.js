const { api } = require( './api.js' );
const {
	getCurrentPageLocation,
	addReturnToParams,
	handleTempUserRedirect
} = require( '../utils.js' );
const {
	viewUiTags
} = require( '../repoSettings.json' );

/**
 * Updating statements and checking for TempUserAccount
 *
 * @param {string} entityId The ID of the entity to update
 * @param {Array} statements
 */
const updateStatements = async function ( entityId, statements ) {
	const location = getCurrentPageLocation();

	let params = {
		action: 'wbeditentity',
		id: entityId,
		data: JSON.stringify( { claims: statements } )
	};

	if ( viewUiTags.length ) {
		params.tags = viewUiTags;
	}

	// Add return-to parameters for temporary account redirect support (T407335)
	params = addReturnToParams( params, location );

	const response = await api.postWithEditToken( api.assertCurrentUser( params ) );

	if ( handleTempUserRedirect( response ) ) {
		return new Promise( () => {} );
	}

	if ( !response.entity || !response.entity.claims ) {
		throw new Error( 'Invalid API response: missing entity.claims' );
	}

	return response.entity.claims;
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

/**
 * @param {Array} propertyIds
 * @returns {Promise<Object>} a mapping of each propertyId to its Html
 */
const renderPropertyLinkHtml = async function ( propertyIds ) {
	const idsToFetch = propertyIds.slice();
	const results = {};

	// wbformatentities is limited to 50 entities, so make calls in batches of 50 or less
	let batch;
	while ( ( batch = idsToFetch.splice( 0, 50 ) ).length > 0 ) {
		Object.assign(
			results,
			( await api.get( {
				action: 'wbformatentities',
				generate: 'text/html',
				ids: batch
			} ) ).wbformatentities
		);
	}
	return results;
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
