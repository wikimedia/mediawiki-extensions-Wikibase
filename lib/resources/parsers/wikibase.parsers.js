/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vp, dv ) {
	'use strict';

	var parserStore = new vp.ValueParserFactory( vp.NullParser );

	parserStore.registerDataValueParser(
		wb.EntityIdParser,
		wb.EntityId.TYPE
	);

	parserStore.registerDataValueParser(
		wb.GlobeCoordinateParser,
		dv.GlobeCoordinateValue.TYPE
	);

	parserStore.registerDataValueParser(
		wb.QuantityParser,
		dv.QuantityValue.TYPE
	);

	parserStore.registerDataValueParser(
		vp.StringParser,
		dv.StringValue.TYPE
	);

	parserStore.registerDataValueParser(
		vp.TimeParser,
		dv.TimeValue.TYPE
	);

}( wikibase, valueParsers, dataValues ) );
