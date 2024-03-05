/**
 * JavaScript implementation of \Wikibase\Lib\LanguageFallbackIndicator.
 *
 * @license GPL-2.0-or-later
 */
wikibase.view.languageFallbackIndicator = ( function ( wb ) {
	'use strict';

	var SELF = {};

	/**
	 * @param {Term} term The term for which a fallback is potentially shown.
	 * @param {string} requestedLanguage The language code which was requested (generally the user language).
	 * @return {string} HTML
	 */
	SELF.getHtml = function getHtml( term, requestedLanguage ) {
		var actualLanguage = term.getLanguageCode();
		// sourceLanguage does not apply, no transliteration in JS

		var isFallback = actualLanguage !== requestedLanguage;
		// isTransliteration does not apply

		if ( !isFallback ) {
			return '';
		}

		if (
			this._getBaseLanguage( actualLanguage ) === this._getBaseLanguage( requestedLanguage )
				|| actualLanguage === 'mul'
		) {
			return '';
		}

		var text = wb.getLanguageNameByCodeForTerms( actualLanguage );

		var classes = 'wb-language-fallback-indicator';
		var attributes = { class: classes };

		var html = mw.html.element( 'sup', attributes, text );
		return '\u{00A0}' + html;
	};

	/**
	 * @private
	 * @param {string} languageCode
	 * @return {string}
	 */
	SELF._getBaseLanguage = function ( languageCode ) {
		return languageCode.replace( /-.*/, '' );
	};

	return SELF;
}( wikibase ) );
