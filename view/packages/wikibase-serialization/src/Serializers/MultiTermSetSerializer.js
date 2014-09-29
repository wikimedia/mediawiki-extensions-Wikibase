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
MODULE.MultiTermSetSerializer = util.inherit( 'WbMultiTermSetSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.MultiTermSet} multiTermSet
	 * @return {Object}
	 */
	serialize: function( multiTermSet ) {
		if( !( multiTermSet instanceof wb.datamodel.MultiTermSet ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.MultiTermSet' );
		}

		var serialization = {},
			multiTermSerializer = new MODULE.MultiTermSerializer(),
			languageCodes = multiTermSet.getKeys();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = multiTermSerializer.serialize(
				multiTermSet.getByKey( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
