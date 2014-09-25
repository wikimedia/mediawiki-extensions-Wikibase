/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for TermGroupList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermGroupListSerializer = util.inherit( 'WbTermGroupListSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.TermGroupList} termGroupList
	 * @return {Object}
	 */
	serialize: function( termGroupList ) {
		if( !( termGroupList instanceof wb.datamodel.TermGroupList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.TermGroupList' );
		}

		var serialization = {},
			termGroupSerializer = new MODULE.TermGroupSerializer(),
			languageCodes = termGroupList.getLanguages();

		for( var i = 0; i < languageCodes.length; i++ ) {
			serialization[languageCodes[i]] = termGroupSerializer.serialize(
				termGroupList.getByLanguage( languageCodes[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
