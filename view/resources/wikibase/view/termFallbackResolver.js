/**
 * Resolve a term map against a language fallback chain.
 *
 * @license GPL-2.0-or-later
 */
wikibase.view.termFallbackResolver = ( function ( wb ) {
	'use strict';

	var SELF = {};

	/**
	 * @param {TermMap} terms
	 * @param {string} requestedLanguage
	 * @return {Term|null}
	 */
	SELF.getTerm = function ( terms, requestedLanguage ) {
		if ( terms.hasItemForKey( requestedLanguage ) ) {
			return terms.getItemByKey( requestedLanguage );
		}

		if ( !wb.fallbackChains || wb.fallbackChains.length !== undefined ) {
			return null;
		}
		var chain = wb.fallbackChains[ requestedLanguage ];

		if ( !chain ) { // language may be unknown, e.g. ?uselang=qqx
			// we don’t need to know if mul is enabled,
			// if it’s disabled then the terms won’t have an entry for it anyway
			chain = [ 'mul', 'en' ];
		}

		// TODO: should be a for-of loop as soon as we can use #ES6
		for ( var i = 0; i < chain.length; i++ ) {
			var langCode = chain[ i ];
			if ( terms.hasItemForKey( langCode ) ) {
				return terms.getItemByKey( langCode );
			}
		}
		return null;
	};

	return SELF;
}( wikibase ) );
