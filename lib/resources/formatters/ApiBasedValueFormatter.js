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
		 *
		 * @return {$.Promise}
		 */
		format: function( dataValue, dataTypeId, outputType ) {
			var deferred = $.Deferred();

			// TODO: Remove accessing wb.__formattedValues along with removing
			// wikibase.ui.initFormattedValues resource loader module.
			if( outputType === 'text/html' && wb.__formattedValues ) {
				var dataValueJson = JSON.stringify( dataValue.toJSON() );
				if ( wb.__formattedValues[dataValueJson] !== undefined ) {
					deferred.resolve( wb.__formattedValues[dataValueJson], dataValue );
					return deferred.promise();
				}
			}

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
				deferred.reject( code, details );
			} );

			return deferred.promise();
		}

	} );

}( jQuery, wikibase, valueFormatters, util ) );
