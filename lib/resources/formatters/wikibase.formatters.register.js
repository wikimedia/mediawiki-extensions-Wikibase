/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, vf, dv ) {
	'use strict';

	// Register Wikibase specific formatter:

	mw.ext.valueFormatters.valueFormatterProvider.registerFormatter(
		dv.QuantityValue.TYPE,
		wb.formatters.QuantityFormatter
	);

}( mediaWiki, wikibase, valueFormatters, dataValues ) );
