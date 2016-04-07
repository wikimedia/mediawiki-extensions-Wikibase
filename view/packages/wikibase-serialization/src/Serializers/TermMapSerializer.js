( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

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
	 * @param {wikibase.datamodel.TermMap} termMap
	 * @return {Object}
	 *
	 * @throws {Error} if termMap is not a TermMap instance.
	 */
	serialize: function( termMap ) {
		if( !( termMap instanceof wb.datamodel.TermMap ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.TermMap' );
		}

		var serialization = {},
			termSerializer = new MODULE.TermSerializer(),
			languageCodes = termMap.getKeys();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = termSerializer.serialize(
				termMap.getItemByKey( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
