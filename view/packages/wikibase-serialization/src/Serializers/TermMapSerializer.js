/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermMapSerializer = util.inherit( 'WbTermMapSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.TermMap} termMap
	 * @return {Object}
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
