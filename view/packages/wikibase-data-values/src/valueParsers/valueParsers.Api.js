/**
 * @file
 * @ingroup ValueParsers
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( mw, vp, $, dv ) {
	'use strict';

	/**
	 * ValueParsers API.
	 * @since 0.1
	 * @type Object
	 */
	vp.api = {};

	/**
	 * Makes an request to the API to parse values on the server side. Will return a jQuery.Promise
	 * which will be resolved if the parsing is successful or rejected if it fails or the API can't
	 * be reached.
	 *
	 * @since 0.1
	 *
	 * @param {String} parser
	 * @param {Array} values
	 * @param {Object} options
	 *
	 * @return $.Promise
	 */
	vp.api.parseValues = function( parser, values, options ) {
		var api = new mw.Api(),
			deferred = $.Deferred();

		options = options || {};

		api.get( {
			action: 'parsevalue',
			parser: parser,
			values: values.join( '|' ),
			options: $.toJSON( options )
		} ).done( function( apiResult ) {
			if ( apiResult.hasOwnProperty( 'results' ) ) {

				if( apiResult.results.length === 0 ) {
					deferred.reject( 'Parse API returned an empty result set.' );
					return;
				}

				var dataValues = [];

				for ( var i in apiResult.results ) {
					var result = apiResult.results[i];

					if ( result.hasOwnProperty( 'value' ) && result.hasOwnProperty( 'type' ) ) {
						try {
							dataValues.push( dv.newDataValue( result.type, result.value ) );
						}
						catch ( error ) {
							deferred.reject( error.hasOwnProperty( 'message' ) ? error.message : 'Unknown error during value unserialization' );
						}
					}
					else {
						deferred.reject( result.hasOwnProperty( 'error' ) ? result.error : 'Unknown error from the API' );
					}
				}

				deferred.resolve( dataValues );
			}
			else {
				deferred.reject( 'The parse API returned an unexpected result' );
			}
		} ).fail( function( apiResult ) {
			deferred.reject( 'Communication with the parsing API failed' );
		} );

		return deferred.promise();
	};

}( mediaWiki, valueParsers, jQuery, dataValues ) );
