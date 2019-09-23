/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, vp, dv ) {
	'use strict';

	var getApiBasedValueParserConstructor = require( './getApiBasedValueParserConstructor.js' );

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @return {valueParsers.ValueParserStore}
	 */
	module.exports = function ( api ) {
		var apiCaller = new wb.api.ParseValueCaller( api ),
			ApiBasedValueParser = getApiBasedValueParserConstructor( apiCaller ),
			parserStore = new vp.ValueParserStore( vp.NullParser );

		parserStore.registerDataValueParser(
			vp.StringParser,
			dv.StringValue.TYPE
		);

		// API-based parsers
		// FIXME: Get this configuration from the backend.
		var parserIdToDataValueType = {
			globecoordinate: dv.GlobeCoordinateValue.TYPE,
			monolingualtext: dv.MonolingualTextValue.TYPE,
			quantity: dv.QuantityValue.TYPE,
			time: dv.TimeValue.TYPE,
			'wikibase-entityid': wb.datamodel.EntityId.TYPE
		};

		// eslint-disable-next-line jquery/no-each-util
		$.each( parserIdToDataValueType, function ( parserId, dvType ) {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataValueParser( Parser, dvType );
		} );

		parserStore.registerDataTypeParser(
			util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: 'commonsMedia' }
			),
			'commonsMedia'
		);

		parserStore.registerDataTypeParser(
			util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: 'geo-shape' }
			),
			'geo-shape'
		);

		parserStore.registerDataTypeParser(
			util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: 'tabular-data' }
			),
			'tabular-data'
		);

		parserStore.registerDataTypeParser(
			util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: 'url' }
			),
			'url'
		);

		parserStore.registerDataTypeParser(
			util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: 'external-id' }
			),
			'external-id'
		);

		return parserStore;
	};

}( wikibase, valueParsers, dataValues ) );
