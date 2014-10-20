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
MODULE.MultiTermMapSerializer = util.inherit( 'WbMultiTermMapSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.MultiTermMap} multiTermMap
	 * @return {Object}
	 */
	serialize: function( multiTermMap ) {
		if( !( multiTermMap instanceof wb.datamodel.MultiTermMap ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.MultiTermMap' );
		}

		var serialization = {},
			multiTermSerializer = new MODULE.MultiTermSerializer(),
			languageCodes = multiTermMap.getKeys();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = multiTermSerializer.serialize(
				multiTermMap.getItemByKey( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
