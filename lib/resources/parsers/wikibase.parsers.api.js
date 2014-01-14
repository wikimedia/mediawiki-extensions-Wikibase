/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $, dv ) {
	'use strict';

	wb.parsers = wb.parsers || {};

	var api = new mw.Api();

	/**
	 * ValueParsers API.
	 * @since 0.5
	 * @type {Object}
	 */
	wb.parsers.api = {};

	/**
	 * Makes an request to the API to parse values on the server side. Will return a jQuery.Promise
	 * which will be resolved if the parsing is successful or rejected if it fails or the API can't
	 * be reached.
	 * @since 0.5
	 *
	 * @param {string} parser
	 * @param {string[]} values
	 * @param {Object} options
	 * @return {$.Promise}
	 */
	wb.parsers.api.parseValues = function( parser, values, options ) {
		var deferred = $.Deferred();

		options = options || {};

		api
		.get( {
			action: 'wbparsevalue',
			parser: parser,
			values: values.join( '|' ),
			options: $.toJSON( options )
		} )
		.done( function( response ) {
			if( !response.results ) {
				deferred.reject(
					'result-unexpected',
					'The parse API returned an unexpected result'
				);
				return;
			}

			if( response.results.length === 0 ) {
				deferred.reject( 'result-empty', 'Parse API returned an empty result set.' );
				return;
			}

			var dataValues = [];

			for ( var i in response.results ) {
				try{
					dataValues.push( unserializeResult( response.results[i] ) );
				} catch( e ) {
					deferred.reject( e.name, e.message );
				}
			}

			deferred.resolve( dataValues );

		} ).fail( function( code, details ) {
			deferred.reject( code, details );
		} );

		return deferred.promise();
	};

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

}( mediaWiki, wikibase, jQuery, dataValues ) );
