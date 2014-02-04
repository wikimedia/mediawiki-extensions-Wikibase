/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dv ) {
	'use strict';

	// Register Wikibase specific parsers:

	mw.ext.valueParsers.valueParserProvider.registerDataValueParser(
		wb.EntityIdParser,
		wb.EntityId.TYPE
	);

	mw.ext.valueParsers.valueParserProvider.registerDataValueParser(
		wb.GlobeCoordinateParser,
		dv.GlobeCoordinateValue.TYPE
	);

	mw.ext.valueParsers.valueParserProvider.registerDataValueParser(
		wb.TimeParser,
		dv.TimeValue.TYPE
	);

	mw.ext.valueParsers.valueParserProvider.registerDataValueParser(
		wb.QuantityParser,
		dv.QuantityValue.TYPE
	);

}( mediaWiki, wikibase, dataValues ) );
