/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for Term objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.TermSerializer = util.inherit( 'WbTermSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Term} term
	 * @return {Object}
	 */
	serialize: function( term ) {
		if( !( term instanceof wb.datamodel.Term ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Term' );
		}

		return {
			language: term.getLanguageCode(),
			value: term.getText()
		};
	}
} );

}( wikibase, util ) );
