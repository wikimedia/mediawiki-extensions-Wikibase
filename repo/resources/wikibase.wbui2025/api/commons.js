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
 * Search for languages
 *
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @return {Promise<Object>} Promise resolving to search results
 */
const searchLanguages = function ( searchTerm, offset = 0 ) {
	return api.get( api.assertCurrentUser( {
		action: 'languagesearch',
		search: searchTerm,
		language: mw.config.get( 'wgUserLanguage' ),
		continue: offset
	} ) ).then( ( response ) => response.languagesearch );
};

/**
 * Transform language search results into menu items format
 *
 * @param {Object} searchResults Array of search results from API
 * @return {Array} Array of menu items with label, value, and description
 */
const transformLanguageSearchResults = function ( searchResults ) {
	if ( !searchResults || searchResults.length === 0 ) {
		return [];
	}

	const result = [];
	for ( const langCode of Object.keys( searchResults ) ) {
		const lang = searchResults[ langCode ];
		result.push( {
			label: lang,
			value: langCode
		} );
	}
	return result;
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
 * Search for media (files) on Commons
 *
 * @param {string} searchTerm The search term
 * @param {number} offset Optional result offset for pagination
 * @returns {Promise<Object>} Promise resolving to search results
 */
const searchCommonsMedia = function ( searchTerm, offset ) {
	return foreignApi( 'https://commons.wikimedia.org/w/api.php' ).get( {
		action: 'query',
		list: 'search',
		srsearch: searchTerm,
		srnamespace: 6, // NS_FILE
		srlimit: 10,
		sroffset: offset
	} );
};

/**
 * Search the repo for entities with a matching label
 *
 * @param {string} searchTerm
 * @param {string} entityType
 * @param {number} [offset]
 * @returns {Promise<*>}
 */
const searchForEntities = async function ( searchTerm, entityType, offset ) {
	return api.get( api.assertCurrentUser( {
		action: 'wbsearchentities',
		search: searchTerm,
		type: entityType,
		language: mw.config.get( 'wgUserLanguage' ),
		continue: offset
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
 * Transform entity search results into menu items format using the conceptUri as
 * the menu item value
 *
 * @param {Array} searchResults Array of search results from API
 * @return {Array} Array of menu items with label, value, and description
 */
const transformEntityByConceptUriSearchResults = function ( searchResults ) {
	if ( !searchResults || searchResults.length === 0 ) {
		return [];
	}

	return searchResults.map( ( result ) => ( {
		label: result.label,
		value: result.concepturi,
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
	searchLanguages,
	searchCommonsMedia,
	transformSearchResults,
	transformEntitySearchResults,
	transformEntityByConceptUriSearchResults,
	transformLanguageSearchResults
};
