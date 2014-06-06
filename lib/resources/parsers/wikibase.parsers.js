/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.parsers = wikibase.parsers || {};

wikibase.parsers.store = ( function( wb, vp, dv ) {
	'use strict';

	var parserStore = new vp.ValueParserStore( vp.NullParser );

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
		wb.TimeParser,
		dv.TimeValue.TYPE
	);

	parserStore.registerDataValueParser(
		wb.MonolingualTextParser,
		dv.MonolingualTextValue.TYPE
	);

	return parserStore;

}( wikibase, valueParsers, dataValues ) );
