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
const { api, foreignApi } = require( './api.js' );
const tabularDataSearchTerm = 'contentmodel:Tabular.JsonConfig';
const geoShapeDataSearchTerm = 'contentmodel:Map.JsonConfig';

// TODO: See [T403973]. Not sure if that's the correct structure, but I think it will need to be reviewed.
const params = {
	action: 'query',
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
	return foreignApi( tabularDataStorageApiEndpointUrl ).get( Object.assign( {
		srsearch: `Data:${ searchTerm } ${ tabularDataSearchTerm }`,
		sroffset: offset
	}, params ) );
};

/**
 * Search for geographic shapes on Commons
 *
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @return {Promise<Object>} Promise resolving to search results
 */
const searchGeoShapes = function ( searchTerm, offset = 0 ) {
	return foreignApi( geoShapeStorageApiEndpointUrl ).get( Object.assign( {
		srsearch: `Data:${ searchTerm } ${ geoShapeDataSearchTerm }`,
		sroffset: offset
	}, params ) );
};

/**
 * Search the repo for entities with a matching label
 *
 * @param {string} searchTerm
 * @param {string} entityType
 * @returns {Promise<*>}
 */
const searchForEntities = async function ( searchTerm, entityType ) {
	return api.get( api.assertCurrentUser( {
		action: 'wbsearchentities',
		search: searchTerm,
		type: entityType,
		language: mw.config.get( 'wgUserLanguage' )
	} ) ).then( ( response ) => response.search );
};

/**
 * Transform entity search results into menu items format
 *
 * @param {Array} searchResults Array of search results from API
 * @return {Array} Array of menu items with label, value, and description
 */
const transformEntitySearchResults = function ( searchResults ) {
	if ( !searchResults || searchResults.length === 0 ) {
		return [];
	}

	return searchResults.map( ( result ) => ( {
		label: result.label,
		value: result.id,
		description: result.description
	} ) );
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
	searchForEntities,
	searchTabularData,
	searchGeoShapes,
	transformSearchResults,
	transformEntitySearchResults
};
