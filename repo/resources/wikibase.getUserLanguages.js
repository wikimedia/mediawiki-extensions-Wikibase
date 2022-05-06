/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var termLanguages = require( './termLanguages.json' );

	function filterInvalidTermsLanguages( languages ) {
		return languages.filter( function ( language ) {
			return termLanguages.indexOf( language ) !== -1;
		} );
	}

	/**
	 * @return {string[]} An ordered list of languages the user wants to use, the first being her
	 * preferred language, and thus the UI language (currently wgUserLanguage).
	 */
	wb.getUserLanguages = function () {
		var userLanguages = mw.config.get( 'wbUserSpecifiedLanguages', [] ),
			userLanguage = mw.config.get( 'wgUserLanguage' ),
			userLanguageIndex = userLanguages.indexOf( userLanguage ),
			isUlsDefined = mw.uls && $.uls && $.uls.data,
			languages;

		if ( !userLanguages.length && isUlsDefined ) {
			languages = mw.uls.getFrequentLanguageList().slice( 1, 4 );
		} else {
			languages = userLanguages.slice();
			if ( userLanguageIndex !== -1 ) {
				languages.splice( userLanguageIndex, 1 );
			}
		}

		languages = filterInvalidTermsLanguages( languages );
		languages.unshift( userLanguage );
		return languages;
	};
}( wikibase ) );
