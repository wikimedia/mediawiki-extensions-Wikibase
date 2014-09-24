/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Container for sets of labels, descriptions and aliases.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.TermList|null} [labels]
 * @param {wikibase.datamodel.TermList|null} [descriptions]
 * @param {wikibase.datamodel.TermGroupList|null} [aliasGroups]
 */
var SELF
	= wb.datamodel.Fingerprint
	= function WbDataModelFingerprint( labels, descriptions, aliasGroups ) {
		labels = labels || new wb.datamodel.TermList();
		descriptions = descriptions || new wb.datamodel.TermList();
		aliasGroups = aliasGroups || new wb.datamodel.TermGroupList();

		if(
			!( labels instanceof wb.datamodel.TermList )
			|| !( descriptions instanceof wb.datamodel.TermList )
			|| !( aliasGroups instanceof wb.datamodel.TermGroupList )
		) {
			throw new Error( 'Required parameter(s) not specified or not defined properly' );
		}

		this._labels = labels;
		this._descriptions = descriptions;
		this._aliasGroups = aliasGroups;
	};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.TermList}
	 */
	_labels: null,

	/**
	 * @type {wikibase.datamodel.TermList}
	 */
	_descriptions: null,

	/**
	 * @type {wikibase.datamodel.TermGroupList}
	 */
	_aliasGroups: null,

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasLabel: function( languageCode ) {
		return this._labels.hasTermForLanguage( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.TermList}
	 */
	getLabels: function() {
		return this._labels;
	},

	/**
	 * @param {string} languageCode
	 * @return {string}
	 */
	getLabel: function( languageCode ) {
		return this._labels.getByLanguage( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 */
	setLabel: function( term ) {
		this._labels.setTerm( term );
	},

	/**
	 * @param {string} languageCode
	 */
	removeLabel: function( languageCode ) {
		this._labels.removeByLanguage( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasDescription: function( languageCode ) {
		return this._descriptions.hasTermForLanguage( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.TermList}
	 */
	getDescriptions: function() {
		return this._descriptions;
	},

	/**
	 * @param {string} languageCode
	 * @return {string}
	 */
	getDescription: function( languageCode ) {
		this._descriptions.getByLanguage( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 */
	setDescription: function( term ) {
		this._descriptions.setTerm( term );
	},

	/**
	 * @param {string} languageCode
	 */
	removeDescription: function( languageCode ) {
		this._descriptions.removeByLanguage( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasAliasGroup: function( languageCode ) {
		return this._aliasGroups.hasGroupForLanguage( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.TermGroupList}
	 */
	getAliasGroups: function() {
		return this._aliasGroups;
	},

	/**
	 * @param {string} languageCode
	 * @return {wikibase.datamodel.TermGroup}
	 */
	getAliasGroup: function( languageCode ) {
		return this._aliasGroups.getByLanguage( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.TermGroup} aliasGroup
	 */
	setAliasGroup: function( aliasGroup ) {
		this._aliasGroups.setGroup( aliasGroup );
	},

	/**
	 * @param {string} languageCode
	 */
	removeAliasGroup: function( languageCode ) {
		this._aliasGroups.removeByLanguage( languageCode );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._labels.isEmpty()
			&& this._descriptions.isEmpty()
			&& this._aliasGroups.isEmpty();
	},

	/**
	 * @param {*} fingerprint
	 * @return {boolean}
	 */
	equals: function( fingerprint ) {
		if( !( fingerprint instanceof SELF ) ) {
			return false;
		}

		return this._labels.equals( fingerprint.getLabels() )
			&& this._descriptions.equals( fingerprint.getDescriptions() )
			&& this._aliasGroups.equals( fingerprint.getAliasGroups() );
	}

} );

}( wikibase, jQuery ) );
