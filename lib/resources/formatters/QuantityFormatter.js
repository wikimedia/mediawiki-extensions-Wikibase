/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vf ) {
	'use strict';

	var PARENT = wb.formatters.ApiBasedValueFormatter;

	/**
	 * QuantityValue formatter.
	 * @constructor
	 * @extends valueFormatters.ApiBasedValueFormatter
	 * @since 0.5
	 */
	wb.formatters.QuantityFormatter = vf.util.inherit( PARENT, {} );

}( wikibase, valueFormatters ) );
