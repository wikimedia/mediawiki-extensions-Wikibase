( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer,
	datamodel = require( 'wikibase.datamodel' );

/**
 * @class wikibase.serialization.MultiTermMapSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.MultiTermMapSerializer = util.inherit( 'WbMultiTermMapSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {datamodel.MultiTermMap} multiTermMap
	 * @return {Object}
	 *
	 * @throws {Error} if multiTermMap is not a MultiTermMap instance.
	 */
	serialize: function( multiTermMap ) {
		if( !( multiTermMap instanceof datamodel.MultiTermMap ) ) {
			throw new Error( 'Not an instance of datamodel.MultiTermMap' );
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
