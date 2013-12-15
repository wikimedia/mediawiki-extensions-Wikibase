/**
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 *
 * @author Daniel Werner < danweetz@web.de >
 */
( function( vp, dv, $ ) {
	'use strict';

	var PARENT = vp.ValueParser;

	/**
	 * Base constructor for objects representing a value parser which is doing an API request to
	 * the 'ValueParsers' extensions API for parsing a value.
	 *
	 * @constructor
	 * @extends vp.ValueParser
	 * @abstract
	 * @since 0.1
	 */
	vp.ApiBasedValueParser = dv.util.inherit( 'VpApiBasedValueParser', PARENT, {
		/**
		 * The key of the related API parser.
		 * @type String
		 */
		API_VALUE_PARSER_ID: null,

		/**
		 * @see vp.ValueParser.parse
		 * @since 0.1
		 *
		 * @param {String} rawValue
		 * @return $.Promise
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred();

			vp.api.parseValues( this.API_VALUE_PARSER_ID, [ rawValue ], this._options )
				.done( function( results ) {
					// we don't want to give an array, only the one DV related to the given rawValue
					deferred.resolve( results[0] );
				} )
				.fail( function( error, details ) {
					deferred.reject( error, details );
				} );

			return deferred.promise();
		}

	} );

}( valueParsers, dataValues, jQuery ) );
