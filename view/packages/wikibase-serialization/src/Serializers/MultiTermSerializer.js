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
MODULE.MultiTermSerializer = util.inherit( 'WbMultiTermSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.MultiTerm} multiTerm
	 * @return {Object[]}
	 */
	serialize: function( multiTerm ) {
		if( !( multiTerm instanceof wb.datamodel.MultiTerm ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.MultiTerm' );
		}

		var serialization = [],
			languageCode = multiTerm.getLanguageCode(),
			texts = multiTerm.getTexts();

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
