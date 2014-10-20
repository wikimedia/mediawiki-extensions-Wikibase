/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Container for sets of labels, descriptions and aliases.
 * @constructor
 * @since 1.0
 *
 * @param {wikibase.datamodel.TermSet|null} [labels]
 * @param {wikibase.datamodel.TermSet|null} [descriptions]
 * @param {wikibase.datamodel.MultiTermSet|null} [aliases]
 */
var SELF
	= wb.datamodel.Fingerprint
	= function WbDataModelFingerprint( labels, descriptions, aliases ) {
		labels = labels || new wb.datamodel.TermSet();
		descriptions = descriptions || new wb.datamodel.TermSet();
		aliases = aliases || new wb.datamodel.MultiTermSet();

		if(
			!( labels instanceof wb.datamodel.TermSet )
			|| !( descriptions instanceof wb.datamodel.TermSet )
			|| !( aliases instanceof wb.datamodel.MultiTermSet )
		) {
			throw new Error( 'Required parameter(s) not specified or not defined properly' );
		}

		this._labels = labels;
		this._descriptions = descriptions;
		this._aliases = aliases;
	};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.TermSet}
	 */
	_labels: null,

	/**
	 * @type {wikibase.datamodel.TermSet}
	 */
	_descriptions: null,

	/**
	 * @type {wikibase.datamodel.MultiTermSet}
	 */
	_aliases: null,

	/**
	 * @return {wikibase.datamodel.TermSet}
	 */
	getLabels: function() {
		return this._labels;
	},

	/**
	 * @param {string} languageCode
	 * @return {string}
	 */
	getLabelFor: function( languageCode ) {
		return this._labels.getItemByKey( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} label
	 * @return {boolean}
	 */
	hasLabel: function( label ) {
		return this._labels.hasItem( label );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasLabelFor: function( languageCode ) {
		return this._labels.hasItemForKey( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 */
	setLabel: function( term ) {
		this._labels.setItem( term );
	},

	/**
	 * @param {wikibase.datamodel.Term} label
	 */
	removeLabel: function( label ) {
		this._labels.removeItem( label );
	},

	/**
	 * @param {string} languageCode
	 */
	removeLabelFor: function( languageCode ) {
		this._labels.removeItemByKey( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.TermSet}
	 */
	getDescriptions: function() {
		return this._descriptions;
	},

	/**
	 * @param {string} languageCode
	 * @return {string}
	 */
	getDescriptionFor: function( languageCode ) {
		return this._descriptions.getItemByKey( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} description
	 * @return {boolean}
	 */
	hasDescription: function( description ) {
		return this._descriptions.hasItem( description );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasDescriptionFor: function( languageCode ) {
		return this._descriptions.hasItemForKey( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.Term} term
	 */
	setDescription: function( term ) {
		this._descriptions.setItem( term );
	},

	/**
	 * @param {wikibase.datamodel.Term} description
	 */
	removeDescription: function( description ) {
		this._descriptions.removeItem( description );
	},

	/**
	 * @param {string} languageCode
	 */
	removeDescriptionFor: function( languageCode ) {
		this._descriptions.removeItemByKey( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.MultiTermSet}
	 */
	getAliases: function() {
		return this._aliases;
	},

	/**
	 * @param {string} [languageCode]
	 * @return {wikibase.datamodel.MultiTerm}
	 */
	getAliasesFor: function( languageCode ) {
		return this._aliases.getItemByKey( languageCode );
	},

	/**
	 * @param {wikibase.datamodel.MultiTerm} aliases
	 * @return {boolean}
	 */
	hasAliases: function( aliases ) {
		return this._aliases.hasItem( aliases );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasAliasesFor: function( languageCode ) {
		return this._aliases.hasItemForKey( languageCode );
	},

	/**
	 * @param wikibase.datamodel.MultiTerm|wikibase.datamodel.MultiTermSet} aliases
	 */
	setAliases: function( aliases ) {
		if( aliases instanceof wb.datamodel.MultiTerm ) {
			this._aliases.setItem( aliases );
		} else if( aliases instanceof wb.datamodel.MultiTermSet ) {
			this._aliases = aliases;
		} else {
			throw new Error( 'Aliases need to be specified as wb.datamodel.MultiTerm or '
				+ 'wb.datamodel.MultiTermSet instance' );
		}
	},

	/**
	 * @param {wikibase.datamodel.MultiTerm} aliases
	 */
	removeAliases: function( aliases ) {
		this._aliases.removeItem( aliases );
	},

	/**
	 * @param {string} languageCode
	 */
	removeAliasesFor: function( languageCode ) {
		this._aliases.removeItemByKey( languageCode );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._labels.isEmpty()
			&& this._descriptions.isEmpty()
			&& this._aliases.isEmpty();
	},

	/**
	 * @param {*} fingerprint
	 * @return {boolean}
	 */
	equals: function( fingerprint ) {
		return fingerprint === this
			|| fingerprint instanceof SELF
				&& this._labels.equals( fingerprint.getLabels() )
				&& this._descriptions.equals( fingerprint.getDescriptions() )
				&& this._aliases.equals( fingerprint.getAliases() );
	}

} );

}( wikibase, jQuery ) );
