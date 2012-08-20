/**
 * @file
 * @ingroup Wikibase
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, $, undefined ) {
'use strict';

var PARENT = wb.Claim,
	constructor = function( mainSnak, qualifiers, references ) {
		PARENT.call( this, mainSnak, qualifiers );
		this._references = references;
		this._rank = this.RANK.NORMAL;
	};

/**
 * Represents a Wikibase Statement in JavaScript.
 * @constructor
 * @extends wb.Claim
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @param {wb.Snak} mainSnak
 * @param {wb.Snak[]} qualifiers
 * @param {wb.Claim[]} references
 */
wb.Statement = wb.utilities.inherit( PARENT, constructor, {
	/**
	 * Rank enum. Higher values are more preferred.
	 * @type Object
	 */
	RANK: {
		PREFERRED: 2,
		NORMAL: 1,
		DEPRECATED: 0
	},

	/**
	 * @type array
	 * @todo determine whether we should rather model a Reference object for this
	 * @todo think about implementing a ReferenceList/ClaimList rather than having an Array here
	 */
	_references: null,

	/**
	 * @type Number
	 */
	_rank: null,

	/**
	 * Returns all of the statements references.
	 *
	 * sufficient
	 * @return Claim[]
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * Overwrites the current set of the statements references.
	 *
	 * @param Claim[] references
	 */
	setReferences: function( references ) {
		this._references = references;
	},

	/**
	 * Allows to set the statements rank.
	 *
	 * @param {Number} rank one of the RANK enum
	 */
	setRank: function( rank ) {},

	/**
	 * Returns the rank of the statement.
	 *
	 * @return {Number} one of the RANK enum
	 */
	getRank: function() {}
} );

}( wikibase, jQuery ) );
