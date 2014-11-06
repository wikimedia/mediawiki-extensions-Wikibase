/**
 * JavaScript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

this.wikibase = this.wikibase || {};

this.wb = this.wikibase;

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
	wb.getLanguages = function() {
		return ( $.uls !== undefined ) ? $.uls.data.languages : {};
	};

	/**
	 * Returns the name of a language by its language code. If the language code is unknown or ULS
	 * can not provide sufficient language information, the language code is returned.
	 *
	 * @param {string} langCode
	 * @return string
	 */
	wb.getLanguageNameByCode = function( langCode ) {
		var language = wb.getLanguages()[ langCode ];
		if( language && language[2] ) {
			return language[2];
		}
		return langCode;
	};

	/**
	 * Same getLanguageNameByCode but on user UI native language instead, fallbacks
	 * to getLanguageNameByCode in cases native translation wasn't available
	 */
	wb.getNativeLanguageNameByCode = function( langCode ) {
		var ulsLanguages = mw.config.get( 'wgULSLanguages' );
		return ( ulsLanguages && ulsLanguages[langCode] ) ?
			ulsLanguages[langCode] :
			wb.getLanguageNameByCode( langCode );
	};

} )( wikibase, mediaWiki, jQuery );
