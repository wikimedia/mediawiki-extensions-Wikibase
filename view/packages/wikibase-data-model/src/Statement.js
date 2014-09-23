/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, util, $ ) {
'use strict';

var PARENT = wb.datamodel.Claim,
	constructor = function( mainSnak, qualifiers, references, rank, guid ) {
		PARENT.call( this, mainSnak, qualifiers, guid );
		this.setReferences( references || new wb.datamodel.ReferenceList() );
		this.setRank( rank === undefined ? wb.datamodel.Statement.RANK.NORMAL : rank );
	};

/**
 * Represents a Wikibase Statement.
 * @constructor
 * @extends wb.datamodel.Claim
 * @since 0.3
 *
 * @param {wb.datamodel.Snak} mainSnak
 * @param {wb.datamodel.Snak[]} [qualifiers]
 * @param {wikibase.datamodel.ReferenceList} [references]
 * @param {Number} [rank]
 * @param {String|null} [guid] The Global Unique Identifier of this Statement. Can be omitted or null
 *        if this is a new Statement, not yet stored in the database and associated with some entity.
 */
var SELF = wb.datamodel.Statement = util.inherit( 'WbStatement', PARENT, constructor, {
	/**
	 * @type {wikibase.datamodel.ReferenceList}
	 */
	_references: null,

	/**
	 * @see wb.datamodel.Statement.RANK
	 * @type Number
	 */
	_rank: null,

	/**
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * @param {wikibase.datamodel.ReferenceList} references
	 */
	setReferences: function( references ) {
		if( !( references instanceof wb.datamodel.ReferenceList ) ) {
			throw new Error( 'References have to be supplied in a ReferenceList' );
		}
		this._references = references;
	},

	/**
	 * Returns the rank of the statement.
	 *
	 * @return {Number} one of the wb.datamodel.Statement.RANK enum
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
		for( var i in SELF.RANK ) {
			if( SELF.RANK[i] === rank ) {
				this._rank = rank;
				return;
			}
		}
		throw new Error( 'Can not set unknown Statement rank "' + rank + '"' );
	},

	/**
	 * Returns whether this statement is equal to another statement.
	 * @see wb.datamodel.Claim.equals
	 *
	 * @param {*} other
	 * @return {boolean}
	 */
	equals: function( other ) {
		return other instanceof SELF
			&& PARENT.prototype.equals.call( this, other )
			&& this._references.equals( other.getReferences() )
			&& this._rank === other.getRank();
	}
} );

/**
 * Rank enum. Higher values are more preferred.
 * @type Object
 */
SELF.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

/**
 * @see wb.datamodel.Claim.TYPE
 */
SELF.TYPE = 'statement';

}( wikibase, util, jQuery ) );
