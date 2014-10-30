/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
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
	 * @param {string} parser
	 * @param {string[]} values
	 * @param {Object} options
	 * @return {Object} jQuery.Promise
	 *         Resolved parameters:
	 *         - {Object[]} Data value serializations.
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

			var dataValuesSerializations = [];

			for( var i in response.results ) {
				var result = response.results[i];

				if( result.error ) {
					// This is a really strange error format, and it's not supported by wikibase.RepoApiError,
					// so we have to parse it manually here.
					deferred.reject( result.messages[0].name, result.messages[0].html['*'] );
					return;
				}

				if( !( result.value && result.type ) ) {
					deferred.reject( 'result-unexpected', 'Unknown API error' );
					return;
				}

				dataValuesSerializations.push( {
					type: result.type,
					value: result.value
				} );
			}

			deferred.resolve( dataValuesSerializations );

		} ).fail( function( code, details ) {
			deferred.reject( code, wb.RepoApiError.newFromApiResponse( code, details ).detailedMessage );
		} );

		return deferred.promise();
	}
} );

}( wikibase, jQuery ) );
