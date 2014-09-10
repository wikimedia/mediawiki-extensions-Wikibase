/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set of TermGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.TermGroup[]} [termGroups]
 */
var SELF = wb.datamodel.TermGroupList = function WbDataModelTermGroupList( termGroups ) {
	termGroups = termGroups || [];

	this._groups = {};
	this.length = 0;

	for( var i = 0; i < termGroups.length; i++ ) {
		if( !termGroups[i] instanceof wb.datamodel.TermGroup ) {
			throw new Error( 'TermGroupList may contain TermGroup instances only' );
		}

		var languageCode = termGroups[i].getLanguageCode();

		if( this._groups[languageCode] ) {
			throw new Error( 'There may only be one TermGroup per language' );
		}

		this.setGroup( termGroups[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object}
	 */
	_groups: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @param {string} languageCode
	 * @return {wikibase.datamodel.TermGroup|null}
	 */
	getByLanguage: function( languageCode ) {
		return this._groups[languageCode] || null;
	},

	/**
	 * @param {string} languageCode
	 */
	removeByLanguage: function( languageCode ) {
		if( this._groups[languageCode] ) {
			this.length--;
		}
		delete this._groups[languageCode];
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasGroupForLanguage: function( languageCode ) {
		return !!this._groups[languageCode];
	},

	/**
	 * @param {wikibase.datamodel.TermGroup} termGroup
	 */
	setGroup: function( termGroup ) {
		var languageCode = termGroup.getLanguageCode();

		if( termGroup.isEmpty() ) {
			this.removeByLanguage( languageCode );
			return;
		}

		if( !this._groups[languageCode] ) {
			this.length++;
		}

		this._groups[languageCode] = termGroup;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} termGroupList
	 * @return {boolean}
	 */
	equals: function( termGroupList ) {
		if( !( termGroupList instanceof SELF ) ) {
			return false;
		}

		if( this.length !== termGroupList.length ) {
			return false;
		}

		for( var languageCode in this._groups ) {
			if( !termGroupList.hasGroup( this._groups[languageCode] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.TermGroup} termGroup
	 * @return {boolean}
	 */
	hasGroup: function( termGroup ) {
		var languageCode = termGroup.getLanguageCode();
		return this._groups[languageCode] && this._groups[languageCode].equals( termGroup );
	}

} );

}( wikibase, jQuery ) );
