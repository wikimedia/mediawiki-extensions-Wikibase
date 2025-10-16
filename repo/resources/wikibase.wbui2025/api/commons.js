/**
 * Commons API module for searching tabular and geographic shape data
 * See: https://phabricator.wikimedia.org/T403973
 *
 * This module provides functionality to search for tabular data and geographic
 * shapes on Wikimedia Commons through their API.
 */

const {
	tabularDataStorageApiEndpointUrl,
	geoShapeStorageApiEndpointUrl
} = require( '../repoSettings.json' );
const tabularDataSearchTerm = 'contentmodel:Tabular.JsonConfig';
const geoShapeDataSearchTerm = 'contentmodel:Map.JsonConfig';

// TODO: See [T403973]. Not sure if that's the correct structure, but I think it will need to be reviewed.
const params = {
	origin: '*',
	action: 'query',
	format: 'json',
	list: 'search',
	srnamespace: 486,
	srlimit: 10
};

/**
 * Search for tabular data on Commons
 *
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @return {Promise<Object>} Promise resolving to search results
 */
const searchTabularData = function ( searchTerm, offset = 0 ) {
	params.srsearch = `Data:${ searchTerm } ${ tabularDataSearchTerm }`;
	params.sroffset = offset;

	const urlParams = new URLSearchParams( params );
	return fetch( `${ tabularDataStorageApiEndpointUrl }?${ urlParams.toString() }` )
		.then( ( response ) => response.json() );
};

/**
 * Search for geographic shapes on Commons
 *
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @return {Promise<Object>} Promise resolving to search results
 */
const searchGeoShapes = function ( searchTerm, offset = 0 ) {
	params.srsearch = `Data:${ searchTerm } ${ geoShapeDataSearchTerm }`;
	params.sroffset = offset;

	const urlParams = new URLSearchParams( params );
	return fetch( `${ geoShapeStorageApiEndpointUrl }?${ urlParams.toString() }` )
		.then( ( response ) => response.json() );
};

/**
 * Generic search function that routes to appropriate search method based on datatype
 *
 * @param {string} datatype The datatype to search for
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @return {Promise<Object>} Promise resolving to search results
 */
const searchByDatatype = function ( datatype, searchTerm, offset = 0 ) {
	if ( datatype === 'tabular-data' ) {
		return searchTabularData( searchTerm, offset );
	} else if ( datatype === 'geo-shape' ) {
		return searchGeoShapes( searchTerm, offset );
	}

	throw new Error( `Unsupported datatype: ${ datatype }` );
};

/**
 * Transform search results into menu items format
 *
 * @param {Array} searchResults Array of search results from API
 * @return {Array} Array of menu items with label, value, and description
 */
const transformSearchResults = function ( searchResults ) {
	if ( !searchResults || searchResults.length === 0 ) {
		return [];
	}

	return searchResults.map( ( result ) => ( {
		label: result.title.replace( 'File:', '' ),
		value: result.title.replace( 'File:', '' ),
		description: ''
	} ) );
};

module.exports = {
	searchTabularData,
	searchGeoShapes,
	searchByDatatype,
	transformSearchResults
};
