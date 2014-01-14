/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < danweetz@web.de >
 */
( function( $, wb, vp, util ) {
	'use strict';

	wb.parsers = wb.parsers || {};

	var PARENT = vp.ValueParser;

	/**
	 * Base constructor for objects representing a value parser which is doing an API request to
	 * the 'parseValue' API module.
	 * @constructor
	 * @extends valueParsers.ValueParser
	 * @since 0.5
	 */
	wb.parsers.ApiBasedValueParser = util.inherit( 'VpApiBasedValueParser', PARENT, {
		/**
		 * The key of the related API parser.
		 * @type {string}
		 */
		API_VALUE_PARSER_ID: null,

		/**
		 * @see valueParsers.ValueParser.parse
		 * @since 0.5
		 *
		 * @param {string} rawValue
		 * @return {$.Promise}
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred();

			wb.parsers.api.parseValues( this.API_VALUE_PARSER_ID, [rawValue], this._options )
				.done( function( results ) {
					// Return actual DataValue only:
					deferred.resolve( results[0] );
				} )
				.fail( function( code, details ) {
					deferred.reject( code, details );
				} );

			return deferred.promise();
		}

	} );

}( jQuery, wikibase, valueParsers, util ) );
