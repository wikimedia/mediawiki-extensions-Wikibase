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
	if( typeof languageCode !== 'string' || typeof text !== 'string' ) {
		throw new Error( 'Required parameters not specified' );
	}

	this._languageCode = languageCode;
	this._text = text;
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
	 * @return {string}
	 */
	getText: function() {
		return this._text;
	},

	/**
	 * @param {string} text
	 */
	setText: function( text ) {
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
