/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	/**
	 * @return {string[]} An ordered list of languages the user wants to use, the first being her
	 * preferred language, and thus the UI language (currently wgUserLanguage).
	 */

	wb.getUserLanguages = function () {
		var userLanguages = mw.config.get( 'wbUserSpecifiedLanguages' ),
			isUlsDefined = mw.uls && $.uls && $.uls.data,
			languages;

		if ( !userLanguages.length && isUlsDefined ) {
			languages = mw.uls.getFrequentLanguageList().slice( 1, 4 );
		} else {
			languages = userLanguages.slice();
			languages.splice( userLanguages.indexOf( mw.config.get( 'wgUserLanguage' ) ), 1 );
		}

		languages.unshift( mw.config.get( 'wgUserLanguage' ) );
		return languages;
	};
}( wikibase ) );
