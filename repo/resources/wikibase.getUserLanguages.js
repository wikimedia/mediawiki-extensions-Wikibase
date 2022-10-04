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
		var userLanguage = mw.config.get( 'wgUserLanguage' ),
			userSpecifiedLanguages = mw.config.get(
				'wbUserSpecifiedLanguages', [] ),
			userPreferredContentLanguages = mw.config.get(
				'wbUserPreferredContentLanguages', userSpecifiedLanguages );

		// start with the preferred languages as determined by the server
		var languages = userPreferredContentLanguages.slice();

		// if the user did not specify any languages (e.g. in Babel),
		// then the preferred languages are not as useful,
		// so add up to four suggestions from ULS
		if ( !userSpecifiedLanguages.length && mw.uls && $.uls && $.uls.data ) {
			var frequentLanguages = mw.uls.getFrequentLanguageList();
			for ( var i = 0; i < 4 && i < frequentLanguages.length; i++ ) {
				if ( languages.indexOf( frequentLanguages[ i ] ) === -1 ) {
					languages.push( frequentLanguages[ i ] );
				}
			}
		}

		// throw out any invalid languages
		languages = filterInvalidTermsLanguages( languages );

		// ensure the list starts with the UI language
		// (unclear if this is intentionally or accidentally after filterInvalidTermsLanguages())
		var userLanguageIndex = languages.indexOf( userLanguage );
		if ( userLanguageIndex !== -1 ) {
			languages.splice( userLanguageIndex, 1 );
		}
		languages.unshift( userLanguage );

		return languages;
	};
}( wikibase ) );
