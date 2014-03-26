/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, vf, util ) {
	'use strict';

	wb.formatters = wb.formatters || {};

	var PARENT = vf.ValueFormatter;

	/**
	 * Base constructor for objects representing a value formatter which is doing an API request to
	 * the FormatSnakValue API module for formatting a value.
	 * @constructor
	 * @extends valueFormatters.ValueFormatter
	 * @since 0.5
	 */
	wb.formatters.ApiBasedValueFormatter = util.inherit( 'WbApiBasedValueFormatter', PARENT, {
		/**
		 * @see valueFormatters.ValueFormatter.parse
		 * @since 0.1
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @param {string} [dataTypeId]
		 * @param {string} [outputType] The output's preferred MIME type
		 * @return {jQuery.Promise}
		 *         Resolved parameters:
		 *         - {string} Formatted DataValue.
		 *         - {dataValues.DataValues} Original DataValue object.
		 *         Rejected parameters:
		 *         - {string} HTML error message.
		 */
		format: function( dataValue, dataTypeId, outputType ) {
			var deferred = $.Deferred();

			// Since dataTypeId and outputType are optional parameters to this function as well as
			// to wb.formatters.api.formatValue, we use this complicated apply arguments pattern.
			wb.formatters.api.formatValue.apply(
				wb.formatters.api,
				$.makeArray( arguments ).concat( [this._options] )
			)
			.done( function( formattedValue ) {
				deferred.resolve( formattedValue, dataValue );
			} )
			.fail( function( code, details ) {
				var message = code;

				if( typeof details === 'string' ) {
					// MediaWiki API rejecting with a plain string.
					message = details;
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

}( jQuery, wikibase, valueFormatters, util ) );
