( function( wb, util ) {
	'use strict';
	var TermSerializer = require( './TermSerializer.js' );

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.TermMapSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.TermMapSerializer = util.inherit( 'WbTermMapSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {datamodel.TermMap} termMap
	 * @return {Object}
	 *
	 * @throws {Error} if termMap is not a TermMap instance.
	 */
	serialize: function( termMap ) {
		if( !( termMap instanceof datamodel.TermMap ) ) {
			throw new Error( 'Not an instance of datamodel.TermMap' );
		}

		var serialization = {},
			termSerializer = new TermSerializer(),
			languageCodes = termMap.getKeys();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = termSerializer.serialize(
				termMap.getItemByKey( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

module.exports = MODULE.TermMapSerializer;
}( wikibase, util ) );
