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
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {dataValues.DataValues}
		 *         Rejected parameters:
		 *         - {string} HTML error message.
		 */
		parse: function( rawValue ) {
			var deferred = $.Deferred();

			wb.parsers.api.parseValues( this.API_VALUE_PARSER_ID, [rawValue], this._options )
				.done( function( results ) {
					// Return actual DataValue only:
					deferred.resolve( results[0] );
				} )
				.fail( function( code, details ) {
					var message = code;

					if( typeof details === 'string' ) {
						// MediaWiki API rejecting with a plain string.
						message = details;
					} else if( details['error-html'] ) {
						// Wikibase parseValue API module specific HTML error message.
						message = details['error-html'];
					} else if(
						details.error
						&& details.error.messages
						&& details.error.messages.html
						&& details.error.messages.html['*']
					) {
						// HTML message from Wikibase API.
						message = details.error.messages.html['*'];
					} else if( details.error && details.error.info ) {
						// Wikibase API no-HTML error message fall-back.
						message = details.error.info;
					} else if( details.exception ) {
						// Failed MediaWiki API call.
						message = details.exception;
					}

					deferred.reject( message );
				} );

			return deferred.promise();
		}

	} );

}( jQuery, wikibase, valueParsers, util ) );
