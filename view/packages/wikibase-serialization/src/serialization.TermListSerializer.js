/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for TermList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermListSerializer = util.inherit( 'WbTermListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.TermList} termList
	 * @return {Object}
	 */
	serialize: function( termList ) {
		if( !( termList instanceof wb.datamodel.TermList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.TermList' );
		}

		var serialization = {},
			termSerializer = new MODULE.TermSerializer(),
			languageCodes = termList.getLanguages();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = termSerializer.serialize(
				termList.getByLanguage( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
