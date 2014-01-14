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
		 * @return {$.Promise}
		 */
		format: function( dataValue ) {
			var deferred = $.Deferred();

			wb.formatters.api.formatValue( dataValue, this._options )
				.done( function( formattedValue ) {
					deferred.resolve( formattedValue, dataValue );
				} )
				.fail( function( code, details ) {
					deferred.reject( code, details );
				} );

			return deferred.promise();
		}

	} );

}( jQuery, wikibase, valueFormatters, util ) );
