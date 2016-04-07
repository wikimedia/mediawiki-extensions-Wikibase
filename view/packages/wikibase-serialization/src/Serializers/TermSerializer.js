( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.TermSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.TermSerializer = util.inherit( 'WbTermSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Term} term
	 * @return {Object}
	 *
	 * @throws {Error} if term is not a Term instance.
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
