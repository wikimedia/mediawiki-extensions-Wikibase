/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for TermSet objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermSetSerializer = util.inherit( 'WbTermSetSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.TermSet} termSet
	 * @return {Object}
	 */
	serialize: function( termSet ) {
		if( !( termSet instanceof wb.datamodel.TermSet ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.TermSet' );
		}

		var serialization = {},
			termSerializer = new MODULE.TermSerializer(),
			languageCodes = termSet.getKeys();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = termSerializer.serialize(
				termSet.getByKey( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
