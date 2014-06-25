/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
wikibase.parsers = wikibase.parsers || {};

wikibase.parsers.store = ( function( $, wb, vp, dv ) {
	'use strict';

	var parserStore = new vp.ValueParserStore( vp.NullParser );

	parserStore.registerDataValueParser(
		wb.EntityIdParser,
		wb.EntityId.TYPE
	);

	parserStore.registerDataValueParser(
		vp.StringParser,
		dv.StringValue.TYPE
	);

	// API-based parsers
	// FIXME: Get this configuration from the backend.
	var parserIdToDataValueType = {
		'globecoordinate': dv.GlobeCoordinateValue.TYPE,
		'monolingualtext': dv.MonolingualTextValue.TYPE,
		'quantity': dv.QuantityValue.TYPE,
		'time': dv.TimeValue.TYPE
	};

	$.each( parserIdToDataValueType, function( parserId, dvType ) {
		var Parser = util.inherit(
			wb.parsers.ApiBasedValueParser,
			{ API_VALUE_PARSER_ID: parserId }
		);

		parserStore.registerDataValueParser( Parser, dvType );
	} );

	return parserStore;

}( jQuery, wikibase, valueParsers, dataValues ) );
