/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $, undefined ) {
'use strict';

var PARENT = wb.Claim,
	constructor = function( mainSnak, qualifiers, references, rank, guid ) {
		PARENT.call( this, mainSnak, qualifiers, guid );
		this.setReferences( references || [] );
		this.setRank( rank === undefined ? this.RANK.NORMAL : rank );
	};

/**
 * Represents a Wikibase Statement in JavaScript.
 * @constructor
 * @extends wb.Claim
 * @since 0.3
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.Snak} mainSnak
 * @param {wb.Snak[]} [qualifiers]
 * @param {Array} [references] An array of arrays of Snaks or empty array
 * @param {Number} [rank]
 * @param {String} [guid] The Global Unique Identifier of this Statement. This can be omitted if
 *        this is a new reference, not yet stored in the database and associated with some item.
 */
wb.Statement = wb.utilities.inherit( PARENT, constructor, {
	/**
	 * @type Array
	 * @todo determine whether we should rather model a Reference object for this
	 * @todo think about implementing a ReferenceList/ClaimList rather than having an Array here
	 */
	_references: null,

	/**
	 * @see wb.Statement.RANK
	 * @type Number
	 */
	_rank: null,

	/**
	 * Returns all of the statements references.
	 *
	 * sufficient
	 * @return {Array} An array of arrays of Snaks or an empty array.
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * Overwrites the current set of the statements references.
	 *
	 * @param {Array} references An array of arrays of Snaks or an empty array.
	 */
	setReferences: function( references ) {
		if( !$.isArray( references ) ) {
			throw new Error( 'References have to be an array' );
		}
		this._references = references;
	},

	/**
	 * Returns the rank of the statement.
	 *
	 * @return {Number} one of the wb.Statement.RANK enum
	 */
	getRank: function() {
		return this._rank;
	},

	/**
	 * Allows to set the statements rank.
	 *
	 * @param {Number} rank One of the RANK enum
	 */
	setRank: function( rank ) {
		// check if given rank is a known rank, then set it. Otherwise, throw error!
		for( var i in wb.Statement.RANK ) {
			if( wb.Statement.RANK[i] === rank ) {
				this._rank = rank;
				return;
			}
		}
		throw new Error( 'Can not set unknown Statement rank "' + rank + '"' );
	}
} );

/**
 * Rank enum. Higher values are more preferred.
 * @type Object
 */
wb.Statement.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

}( wikibase, jQuery ) );
