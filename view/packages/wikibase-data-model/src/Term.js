/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Represents a term (combination of a language code and a text string).
 * @constructor
 * @since 0.4
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
	 * @type {string}
	 */
	_languageCode: null,

	/**
	 * @type {string}
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
	 */
	setLanguageCode: function( languageCode ) {
		if( languageCode === undefined ) {
			throw new Error( 'Language codes may not be undefined' );
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
		if( !( term instanceof SELF ) ) {
			return false;
		}

		return this._languageCode === term.getLanguageCode() && this._text === term.getText();
	}

} );

}( wikibase, jQuery ) );
