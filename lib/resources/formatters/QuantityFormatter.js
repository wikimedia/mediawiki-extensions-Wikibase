/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

	var PARENT = wb.formatters.ApiBasedValueFormatter;

	/**
	 * QuantityValue formatter.
	 * @constructor
	 * @extends wikibase.formatters.ApiBasedValueFormatter
	 * @since 0.5
	 */
	wb.formatters.QuantityFormatter = util.inherit( PARENT, {} );

}( wikibase, util ) );
