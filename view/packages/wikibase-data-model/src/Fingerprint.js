( function( $ ) {
'use strict';

var TermMap = require( './TermMap.js' ),
	MultiTerm = require( './MultiTerm.js' ),
	MultiTermMap = require( './MultiTermMap.js' );

/**
 * Container for sets of labels, descriptions and aliases.
 * @class Fingerprint
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {TermMap|null} [labels=new TermMap()]
 * @param {TermMap|null} [descriptions=new TermMap()]
 * @param {MultiTermMap|null} [aliases=new MultiTermMap()]
 *
 * @throws {Error} if a required parameter is not specified properly.
 */
var SELF = function WbDataModelFingerprint( labels, descriptions, aliases ) {
		labels = labels || new TermMap();
		descriptions = descriptions || new TermMap();
		aliases = aliases || new MultiTermMap();

		if(
			!( labels instanceof TermMap )
			|| !( descriptions instanceof TermMap )
			|| !( aliases instanceof MultiTermMap )
		) {
			throw new Error( 'Required parameter(s) not specified or not defined properly' );
		}

		this._labels = labels;
		this._descriptions = descriptions;
		this._aliases = aliases;
	};

/**
 * @class Fingerprint
 */
$.extend( SELF.prototype, {
	/**
	 * @property {TermMap}
	 * @private
	 */
	_labels: null,

	/**
	 * @property {TermMap}
	 * @private
	 */
	_descriptions: null,

	/**
	 * @property {MultiTermMap}
	 * @private
	 */
	_aliases: null,

	/**
	 * @return {TermMap}
	 */
	getLabels: function() {
		return this._labels;
	},

	/**
	 * @param {string} languageCode
	 * @return {Term|null}
	 */
	getLabelFor: function( languageCode ) {
		return this._labels.getItemByKey( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @param {Term} label
	 * @return {boolean}
	 */
	hasLabel: function( languageCode, label ) {
		return this._labels.hasItem( languageCode, label );
	},

	/**
	 * @param {string} languageCode
	 * @param {Term|null} term
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
	 * @param {Term} label
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
	 * @return {TermMap}
	 */
	getDescriptions: function() {
		return this._descriptions;
	},

	/**
	 * @param {string} languageCode
	 * @return {Term|null}
	 */
	getDescriptionFor: function( languageCode ) {
		return this._descriptions.getItemByKey( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @param {Term} description
	 * @return {boolean}
	 */
	hasDescription: function( languageCode, description ) {
		return this._descriptions.hasItem( languageCode, description );
	},

	/**
	 * @param {string} languageCode
	 * @param {Term|null} term
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
	 * @param {Term} description
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
	 * @return {MultiTermMap}
	 */
	getAliases: function() {
		return this._aliases;
	},

	/**
	 * @param {string} languageCode
	 * @return {MultiTerm|null}
	 */
	getAliasesFor: function( languageCode ) {
		return this._aliases.getItemByKey( languageCode );
	},

	/**
	 * @param {string} languageCode
	 * @param {MultiTerm} aliases
	 * @return {boolean}
	 */
	hasAliases: function( languageCode, aliases ) {
		return this._aliases.hasItem( languageCode, aliases );
	},

	/**
	 * @param {string|MultiTermMap} languageCodeOrAliases
	 * @param {MultiTerm|null} [aliases]
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

		if( aliases === null || aliases instanceof MultiTerm ) {
			if( !languageCode ) {
				throw new Error( 'Language code the MultiTerm object should be set '
					+ 'for needs to be specified' );
			}
			if ( aliases === null || aliases.isEmpty() ) {
				this._aliases.removeItemByKey( languageCode );
			} else {
				this._aliases.setItem( languageCode, aliases );
			}
		} else if( aliases instanceof MultiTermMap ) {
			if( languageCode ) {
				throw new Error( 'Unable to handle language code when setting a '
					+ 'MultiTermMap' );
			}
			this._aliases = aliases;
		} else {
			throw new Error( 'Aliases need to be specified as MultiTerm or '
				+ 'MultiTermMap instance' );
		}
	},

	/**
	 * @param {string} languageCode
	 * @param {MultiTerm} aliases
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

module.exports = SELF;

}( jQuery ) );
