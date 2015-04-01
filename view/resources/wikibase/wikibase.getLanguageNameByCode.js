/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */
( function ( wb, mw, $ ) {
	'use strict';

	/**
	 * Tries to retrieve Universal Language Selector's set of languages.
	 *
	 * TODO: Further decouple this from ULS. Make the languages known to Wikibase a config thing
	 *  and use ULS as source for that language information, then inject it into Wikibase upon
	 *  initialization. This way, everything beyond extension initialization doesn't have to know
	 *  about ULS.
	 *
	 * @return {Object} Set of languages (empty object when ULS is not available)
	 */
	var getLanguages = function() {
		return $.uls && $.uls.data.languages || {};
	};

	/**
	 * Returns the name of a language in that language, if available (currently requires ULS).
	 * Falls back to the language code.
	 *
	 * @param {string} langCode
	 * @return string
	 */
	var getNativeLanguageName = function( langCode ) {
		var language = getLanguages()[ langCode ];
		return language && language[2] || langCode;
	};

	/**
	 * Returns the name of a language in the UI language, if available (currently requires ULS).
	 * Falls back to getNativeLanguageName, which may fall back to the language code.
	 *
	 * @param {string} langCode
	 * @return string
	 */
	wb.getLanguageNameByCode = function( langCode ) {
		var ulsLanguages = mw.config.get( 'wgULSLanguages' );
		return ulsLanguages && ulsLanguages[langCode] || getNativeLanguageName( langCode );
	};

} )( wikibase, mediaWiki, jQuery );
