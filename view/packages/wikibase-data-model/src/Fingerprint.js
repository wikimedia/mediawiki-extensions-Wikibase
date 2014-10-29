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
 * @param {wikibase.datamodel.TermMap|null} [labels]
 * @param {wikibase.datamodel.TermMap|null} [descriptions]
 * @param {wikibase.datamodel.MultiTermMap|null} [aliases]
 */
var SELF
	= wb.datamodel.Fingerprint
	= function WbDataModelFingerprint( labels, descriptions, aliases ) {
		labels = labels || new wb.datamodel.TermMap();
		descriptions = descriptions || new wb.datamodel.TermMap();
		aliases = aliases || new wb.datamodel.MultiTermMap();

		if(
			!( labels instanceof wb.datamodel.TermMap )
			|| !( descriptions instanceof wb.datamodel.TermMap )
			|| !( aliases instanceof wb.datamodel.MultiTermMap )
		) {
			throw new Error( 'Required parameter(s) not specified or not defined properly' );
		}

		this._labels = labels;
		this._descriptions = descriptions;
		this._aliases = aliases;
	};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.TermMap}
	 */
	_labels: null,

	/**
	 * @type {wikibase.datamodel.TermMap}
	 */
	_descriptions: null,

	/**
	 * @type {wikibase.datamodel.MultiTermMap}
	 */
	_aliases: null,

	/**
	 * @return {wikibase.datamodel.TermMap}
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
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} label
	 * @return {boolean}
	 */
	hasLabel: function( languageCode, label ) {
		return this._labels.hasItem( languageCode, label );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasLabelFor: function( languageCode ) {
		return this._labels.hasItemForKey( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} term
	 */
	setLabel: function( languageCode, term ) {
		this._labels.setItem( languageCode, term );
	},

	/**
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} label
	 */
	removeLabel: function( languageCode, label ) {
		this._labels.removeItem( languageCode, label );
	},

	/**
	 * @param {string} languageCode
	 */
	removeLabelFor: function( languageCode ) {
		this._labels.removeItemByKey( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.TermMap}
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
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} description
	 * @return {boolean}
	 */
	hasDescription: function( languageCode, description ) {
		return this._descriptions.hasItem( languageCode, description );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasDescriptionFor: function( languageCode ) {
		return this._descriptions.hasItemForKey( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} term
	 */
	setDescription: function( languageCode, term ) {
		this._descriptions.setItem( languageCode, term );
	},

	/**
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.Term} description
	 */
	removeDescription: function( languageCode, description ) {
		this._descriptions.removeItem( languageCode, description );
	},

	/**
	 * @param {string} languageCode
	 */
	removeDescriptionFor: function( languageCode ) {
		this._descriptions.removeItemByKey( languageCode );
	},

	/**
	 * @return {wikibase.datamodel.MultiTermMap}
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
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.MultiTerm} aliases
	 * @return {boolean}
	 */
	hasAliases: function( languageCode, aliases ) {
		return this._aliases.hasItem( languageCode, aliases );
	},

	/**
	 * @param {string} languageCode
	 * @return {boolean}
	 */
	hasAliasesFor: function( languageCode ) {
		return this._aliases.hasItemForKey( languageCode );
	},

	/**
	 * @param {string} [languageCode]
	 * @param {wikibase.datamodel.MultiTerm|wikibase.datamodel.MultiTermMap} aliases
	 */
	setAliases: function( languageCode, aliases ) {
		if( typeof languageCode !== 'string' ) {
			aliases = languageCode;
			languageCode = undefined;
		}

		if( aliases instanceof wb.datamodel.MultiTerm ) {
			if( !languageCode ) {
				throw new Error( 'Language code the wb.datamodel.MultiTerm object should be set '
					+ 'for needs to be specified' );
			}
			this._aliases.setItem( languageCode, aliases );
		} else if( aliases instanceof wb.datamodel.MultiTermMap ) {
			this._aliases = aliases;
		} else {
			throw new Error( 'Aliases need to be specified as wb.datamodel.MultiTerm or '
				+ 'wb.datamodel.MultiTermMap instance' );
		}
	},

	/**
	 * @param {string} languageCode
	 * @param {wikibase.datamodel.MultiTerm} aliases
	 */
	removeAliases: function( languageCode, aliases ) {
		this._aliases.removeItem( languageCode, aliases );
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
