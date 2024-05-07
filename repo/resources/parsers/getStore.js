/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, vp, dv ) {
	'use strict';

	var getApiBasedValueParserConstructor = require( './getApiBasedValueParserConstructor.js' ),
		registeredTypeIds = require( '../config.json' ).registeredTypeIds;

	/**
	 * @param {wikibase.api.RepoApi} api
	 * @return {valueParsers.ValueParserStore}
	 */
	module.exports = function ( api ) {
		var apiCaller = new wb.api.ParseValueCaller( api ),
			ApiBasedValueParser = getApiBasedValueParserConstructor( apiCaller ),
			parserStore = new vp.ValueParserStore( vp.NullParser );

		// eslint-disable-next-line no-jquery/no-each-util
		$.each( registeredTypeIds, function ( _idx, parserId ) {
			var Parser = util.inherit(
				ApiBasedValueParser,
				{ API_VALUE_PARSER_ID: parserId }
			);

			parserStore.registerDataTypeParser( Parser, parserId );
		} );

		return parserStore;
	};

}( wikibase, valueParsers, dataValues ) );
