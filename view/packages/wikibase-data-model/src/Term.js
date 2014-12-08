( function( wb, $ ) {
'use strict';

/**
 * Combination of a language code and a text string.
 * @class wikibase.datamodel.Term
 * @since 1.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} languageCode
 * @param {string} text
 */
var SELF = wb.datamodel.Term = function WbDataModelTerm( languageCode, text ) {
	this.setLanguageCode( languageCode );
	this.setText( text );
};

$.extend( SELF.prototype, {
	/**
	 * @property {string}
	 * @private
	 */
	_languageCode: null,

	/**
	 * @property {string}
	 * @private
	 */
	_text: null,

	/**
	 * @return {string}
	 */
	getLanguageCode: function() {
		return this._languageCode;
	},

	/**
	 * @param {string} languageCode
	 *
	 * @throws {Error} if language code is not a string.
	 */
	setLanguageCode: function( languageCode ) {
		if( typeof languageCode !== 'string' ) {
			throw new Error( 'Language code has to be a string' );
		}
		this._languageCode = languageCode;
	},

	/**
	 * @return {string}
	 */
	getText: function() {
		return this._text;
	},

	/**
	 * @param {string} text
	 *
	 * @throws {Error} if text is not a string.
	 */
	setText: function( text ) {
		if( typeof text !== 'string' ) {
			throw new Error( 'Text needs to be a string' );
		}
		this._text = text;
	},

	/**
	 * @param {*} term
	 * @return {boolean}
	 */
	equals: function( term ) {
		return term === this
			|| term instanceof SELF
				&& this._languageCode === term.getLanguageCode()
				&& this._text === term.getText();
	}

} );

}( wikibase, jQuery ) );
