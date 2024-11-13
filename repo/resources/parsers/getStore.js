/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, vp ) {
	'use strict';

	var getApiBasedValueParserConstructor = require( './getApiBasedValueParserConstructor.js' ),
		{ dataTypes, valueTypes } = require( '../config.json' );

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @return {valueParsers.ValueParserStore}
	 */
	module.exports = function ( api ) {
		var apiCaller = new wb.api.ParseValueCaller( api ),
			ApiBasedValueParser = getApiBasedValueParserConstructor( apiCaller ),
			parserStore = new vp.ValueParserStore( vp.NullParser );

		dataTypes.forEach( ( parserId ) => {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataTypeParser( Parser, parserId );
		} );
		valueTypes.forEach( ( parserId ) => {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataValueParser( Parser, parserId );
		} );

		return parserStore;
	};

}( wikibase, valueParsers, dataValues ) );
