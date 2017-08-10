( function( wb, $ ) {
'use strict';

/**
 * Container for sets of labels, descriptions and aliases.
 * @class wikibase.datamodel.Fingerprint
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.TermMap|null} [labels=new wikibase.datamodel.TermMap()]
 * @param {wikibase.datamodel.TermMap|null} [descriptions=new wikibase.datamodel.TermMap()]
 * @param {wikibase.datamodel.MultiTermMap|null} [aliases=new wikibase.datamodel.MultiTermMap()]
 *
 * @throws {Error} if a required parameter is not specified properly.
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

/**
 * @class wikibase.datamodel.Fingerprint
 */
$.extend( SELF.prototype, {
	/**
	 * @property {wikibase.datamodel.TermMap}
	 * @private
	 */
	_labels: null,

	/**
	 * @property {wikibase.datamodel.TermMap}
	 * @private
	 */
	_descriptions: null,

	/**
	 * @property {wikibase.datamodel.MultiTermMap}
	 * @private
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
	 * @return {wikibase.datamodel.Term|null}
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
	 * @param {wikibase.datamodel.Term|null} term
	 */
	setLabel: function( languageCode, term ) {
		if ( term === null || term.getText() === '' ) {
			this._labels.removeItemByKey( languageCode );
		} else {
			this._labels.setItem( languageCode, term );
		}
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
	 * @return {wikibase.datamodel.Term|null}
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
	 * @param {wikibase.datamodel.Term|null} term
	 */
	setDescription: function( languageCode, term ) {
		if ( term === null || term.getText() === '' ) {
			this._descriptions.removeItemByKey( languageCode );
		} else {
			this._descriptions.setItem( languageCode, term );
		}
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
	 * @param {string} languageCode
	 * @return {wikibase.datamodel.MultiTerm|null}
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
	 * @param {string|wikibase.datamodel.MultiTermMap} languageCodeOrAliases
	 * @param {wikibase.datamodel.MultiTerm|null} [aliases]
	 *
	 * @throws {Error} when passing a MultiTerm without a language code.
	 * @throws {Error} when passing a MultiTermMap with a language code.
	 * @throws {Error} when neither passing a MultiTerm nor a MultiTermMap object.
	 */
	setAliases: function( languageCodeOrAliases, aliases ) {
		var languageCode;

		if( typeof languageCodeOrAliases === 'string' ) {
			languageCode = languageCodeOrAliases;
		} else {
			aliases = languageCodeOrAliases;
		}

		if( aliases === null || aliases instanceof wb.datamodel.MultiTerm ) {
			if( !languageCode ) {
				throw new Error( 'Language code the wb.datamodel.MultiTerm object should be set '
					+ 'for needs to be specified' );
			}
			if ( aliases === null || aliases.isEmpty() ) {
				this._aliases.removeItemByKey( languageCode );
			} else {
				this._aliases.setItem( languageCode, aliases );
			}
		} else if( aliases instanceof wb.datamodel.MultiTermMap ) {
			if( languageCode ) {
				throw new Error( 'Unable to handle language code when setting a '
					+ 'wb.datamodel.MultiTermMap' );
			}
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
