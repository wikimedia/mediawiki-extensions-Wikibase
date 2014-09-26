/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set of terms.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Term[]} [terms]
 */
var SELF = wb.datamodel.TermList = function WbDataModelTermList( terms ) {
	terms = terms || [];

	this._terms = {};
	this.length = 0;

	for( var i = 0; i < terms.length; i++ ) {
		if( !terms[i] instanceof wb.datamodel.Term ) {
			throw new Error( 'TermList may contain Term instances only' );
		}

		if( this._terms[terms[i].getLanguageCode()] ) {
			throw new Error( 'There may only be one Term per language' );
		}

		this.setTerm( terms[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object}
	 */
	_terms: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @return {string[]}
	 */
	getLanguages: function() {
		var languageCodes = [];

		for( var languageCode in this._terms ) {
			languageCodes.push( languageCode );
		}

		return languageCodes;
	},

	/**
	 * @param {string} languageCode
	 * @return {wikibase.datamodel.Term|null}
	 */
	getByLanguage: function( languageCode ) {
		return this._terms[languageCode] || null;
	},

	/**
	 * @param {string} languageCode
	 */
	removeByLanguage: function( languageCode ) {
		if( this._terms[languageCode] ) {
			this.length--;
		}
		delete this._terms[languageCode];
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasTermForLanguage: function( languageCode ) {
		return !!this._terms[languageCode];
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 */
	setTerm: function( term ) {
		if( !( term instanceof wb.datamodel.Term ) ) {
			throw new Error( 'term needs to be an instance of wikibase.datamodel.Term' );
		}

		var languageCode = term.getLanguageCode();

		if( !this._terms[languageCode] ) {
			this.length++;
		}

		this._terms[term.getLanguageCode()] = term;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} termList
	 * @return {boolean}
	 */
	equals: function( termList ) {
		if( termList === this ) {
			return true;
		} else if( !( termList instanceof SELF ) || this.length !== termList.length ) {
			return false;
		}

		for( var languageCode in this._terms ) {
			if( !termList.hasTerm( this._terms[languageCode] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 * @return {boolean}
	 */
	hasTerm: function( term ) {
		if( !( term instanceof wb.datamodel.Term ) ) {
			throw new Error( 'term needs to be an instance of wikibase-datamodel.Term' );
		}
		var languageCode = term.getLanguageCode();
		return this._terms[languageCode] && this._terms[languageCode].equals( term );
	}

} );

}( wikibase, jQuery ) );
