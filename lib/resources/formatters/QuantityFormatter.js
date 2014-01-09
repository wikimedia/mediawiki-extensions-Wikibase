/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, vf ) {
	'use strict';

	var PARENT = wb.formatters.ApiBasedValueFormatter;

	/**
	 * QuantityValue formatter.
	 * @constructor
	 * @extends valueFormatters.ApiBasedValueFormatter
	 * @since 0.5
	 */
	wb.formatters.QuantityFormatter = vf.util.inherit( 'WbQuantityFormatter', PARENT, {
		/**
		 * @see valueFormatters.ValueFormatter.parse
		 */
		format: function( dataValue ) {
			// TODO: Remove accessing wb.__formattedValues along with removing
			// wikibase.ui.initFormattedValues resource loader module.
			if( wb.__formattedValues ) {
				for( var dataValueJson in wb.__formattedValues ) {
					if( JSON.stringify( dataValue.toJSON() ) === dataValueJson ) {
						var deferred = $.Deferred();
						deferred.resolve( wb.__formattedValues[dataValueJson], dataValue );
						return deferred.promise();
					}
				}
			}
			return PARENT.prototype.format( dataValue );
		}
	} );

}( wikibase, jQuery, valueFormatters ) );
