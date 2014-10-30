/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, dv ) {
'use strict';

var MODULE = wb.api;

/**
 * Provides functionality to parse a value using the API.
 * @constructor
 * @since 0.5
 *
 * @param {wikibase.RepoApi} api
 */
var SELF = MODULE.ParseValueCaller = function( api ) {
	this._api = api;
};

$.extend( SELF.prototype, {

	/**
	 * @type {wikibase.RepoApi}
	 */
	_api: null,

	/**
	 * Makes a request to the API to parse values on the server side. Will return a jQuery.Promise
	 * which will be resolved if the parsing is successful or rejected if it fails or the API can't
	 * be reached.
	 * @since 0.5
	 *
	 * @param {string} parser
	 * @param {string[]} values
	 * @param {Object} options
	 * @return {Object} jQuery.Promise
	 *         Resolved parameters:
	 *         - {dataValues.DataValues[]}
	 *         Rejected parameters:
	 *         - {string} Error code.
	 *         - {string} HTML error message.
	 */
	parseValues: function( parser, values, options ) {
		var deferred = $.Deferred();

		options = options || {};

		this._api.parseValue( parser, values, options ).done( function( response ) {
			if( !response.results ) {
				deferred.reject(
					'result-unexpected',
					'The parse API returned an unexpected result'
				);
				return;
			}

			var dataValues = [];

			for( var i in response.results ) {
				var result = response.results[i];

				if( result.error ) {
					// This is a really strange error format, and it's not supported by wikibase.RepoApiError,
					// so we have to parse it manually here.
					deferred.reject( result.messages[0].name, result.messages[0].html['*'] );
					return;
				}

				try {
					dataValues.push( unserializeResult( result ) );
				} catch( error ) {
					deferred.reject( error.name, error.message );
					return;
				}
			}

			deferred.resolve( dataValues );

		} ).fail( function( code, details ) {
			deferred.reject( code, wb.RepoApiError.newFromApiResponse( code, details ).detailedMessage );
		} );

		return deferred.promise();
	}
} );

/**
 * @param {string} result
 * @return {dataValues.DataValue}
 */
function unserializeResult( result ) {
	if( !( result.value && result.type ) ) {
		throw new Error( 'Unknown API error' );
	} else {
		return dv.newDataValue( result.type, result.value );
	}
}

}( wikibase, jQuery, dataValues ) );
