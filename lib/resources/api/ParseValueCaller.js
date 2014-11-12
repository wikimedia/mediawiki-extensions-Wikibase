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
 * @param {wikibase.api.RepoApi} api
 */
var SELF = MODULE.ParseValueCaller = function( api ) {
	this._api = api;
};

$.extend( SELF.prototype, {

	/**
	 * @type {wikibase.api.RepoApi}
	 */
	_api: null,

	/**
	 * Makes a request to the API to parse values on the server side. Will return a jQuery.Promise
	 * which will be resolved if the call is successful or rejected if the API fails or can't be reached.
	 * @since 0.5
	 *
	 * @param {string} parser
	 * @param {string[]} values
	 * @param {Object} options
	 * @return {Object} jQuery.Promise
	 *         Resolved parameters:
	 *         - {Object[]}
	 *         Rejected parameters:
	 *         - {wikibase.api.RepoApiError}
	 */
	parseValues: function( parser, values, options ) {
		var deferred = $.Deferred();

		options = options || {};

		this._api.parseValue( parser, values, options ).done( function( response ) {
			if( !response.results ) {
				deferred.reject( new wb.api.RepoApiError(
					'result-unexpected',
					'The parse API returned an unexpected result'
				) );
				return;
			}

			var dataValuesSerializations = [];

			for( var i in response.results ) {
				var result = response.results[i];

				if( result.error ) {
					// This is a really strange error format, and it's not supported by
					// wikibase.api.RepoApiError.newFromApiResponse, so we have to parse it manually here.
					// See bug 72947.
					deferred.reject( new wb.api.RepoApiError(
						result.messages[0].name,
						result.messages[0].html['*']
					) );
					return;
				}

				if( !( result.value && result.type ) ) {
					deferred.reject( new wb.api.RepoApiError( 'result-unexpected', 'Unknown API error' ) );
					return;
				}

				dataValuesSerializations.push( {
					type: result.type,
					value: result.value
				} );
			}

			deferred.resolve( dataValuesSerializations );

		} ).fail( function( code, details ) {
			deferred.reject( wb.api.RepoApiError.newFromApiResponse( code, details ) );
		} );

		return deferred.promise();
	}
} );

}( wikibase, jQuery ) );
