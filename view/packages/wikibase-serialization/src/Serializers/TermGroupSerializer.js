/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for TermGroup objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermGroupSerializer = util.inherit( 'WbTermGroupSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.TermGroup} termGroup
	 * @return {Object[]}
	 */
	serialize: function( termGroup ) {
		if( !( termGroup instanceof wb.datamodel.TermGroup ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.TermGroup' );
		}

		var serialization = [],
			languageCode = termGroup.getLanguageCode(),
			texts = termGroup.getTexts();

		for( var i = 0; i < texts.length; i++ ) {
			serialization.push( {
				language: languageCode,
				value: texts[i]
			} );
		}

		return serialization;
	}
} );

}( wikibase, util ) );
