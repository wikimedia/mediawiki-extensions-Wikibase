/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dv ) {
	'use strict';

	// Register Wikibase specific formatter:

	mw.ext.valueFormatters.valueFormatterProvider.registerDataValueFormatter(
		wb.formatters.QuantityFormatter,
		dv.QuantityValue.TYPE
	);

}( mediaWiki, wikibase, dataValues ) );
