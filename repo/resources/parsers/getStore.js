/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, vp, dv ) {
	'use strict';

	var getApiBasedValueParserConstructor = require( './getApiBasedValueParserConstructor.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @return {valueParsers.ValueParserStore}
	 */
	module.exports = function ( api ) {
		var apiCaller = new wb.api.ParseValueCaller( api ),
			ApiBasedValueParser = getApiBasedValueParserConstructor( apiCaller ),
			parserStore = new vp.ValueParserStore( vp.NullParser );

		// API-based parsers
		// FIXME: Get this configuration from the backend.
		var parserIdToDataValueType = {
			globecoordinate: dv.GlobeCoordinateValue.TYPE,
			monolingualtext: dv.MonolingualTextValue.TYPE,
			quantity: dv.QuantityValue.TYPE,
			string: dv.StringValue.TYPE,
			time: dv.TimeValue.TYPE,
			'wikibase-entityid': datamodel.EntityId.TYPE
		};

		// eslint-disable-next-line no-jquery/no-each-util
		$.each( parserIdToDataValueType, function ( parserId, dvType ) {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataValueParser( Parser, dvType );
		} );

		var dataTypeParserIDs = [
			'commonsMedia',
			'geo-shape',
			'tabular-data',
			'url',
			'external-id'
		];

		dataTypeParserIDs.forEach( function ( parserId ) {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataTypeParser( Parser, parserId );
		} );

		return parserStore;
	};

}( wikibase, valueParsers, dataValues ) );
