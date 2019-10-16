( function( $ ) {
'use strict';

var ReferenceList = require( './ReferenceList.js' ),
	Claim = require( './Claim.js' );

/**
 * Combination of a claim, a rank and references.
 * @class Statement
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Claim} claim
 * @param {ReferenceList|null} [references=new ReferenceList()]
 * @param {number} [rank=Statement.RANK.NORMAL]
 */
var SELF = function WbDataModelStatement( claim, references, rank ) {
	this.setClaim( claim );
	this.setReferences( references || new ReferenceList() );
	this.setRank( rank === undefined ? SELF.RANK.NORMAL : rank );
};

/**
 * @class Statement
 */
$.extend( SELF.prototype, {
	/**
	 * @property {Claim}
	 * @private
	 */
	_claim: null,

	/**
	 * @property {ReferenceList}
	 * @private
	 */
	_references: null,

	/**
	 * @see Statement.RANK
	 * @property {number}
	 * @private
	 */
	_rank: null,

	/**
	 * @return {Claim}
	 */
	getClaim: function() {
		return this._claim;
	},

	/**
	 * @param {Claim} claim
	 *
	 * @throws {Error} if claim is not a Claim instance.
	 */
	setClaim: function( claim ) {
		if( !( claim instanceof Claim ) ) {
			throw new Error( 'Claim needs to be an instance of Claim' );
		}
		this._claim = claim;
	},

	/**
	 * @return {ReferenceList}
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * @param {ReferenceList} references
	 *
	 * @throws {Error} if references is not a ReferenceList instance.
	 */
	setReferences: function( references ) {
		if( !( references instanceof ReferenceList ) ) {
			throw new Error( 'References have to be supplied in a ReferenceList' );
		}
		this._references = references;
	},

	/**
	 * @see Statement.RANK
	 *
	 * @return {number}
	 */
	getRank: function() {
		return this._rank;
	},

	/**
	 * @see Statement.RANK
	 *
	 * @param {number} rank
	 *
	 * @throws {Error} if rank is not defined in Statement.RANK.
	 */
	setRank: function( rank ) {
		for( var i in SELF.RANK ) {
			if( SELF.RANK[i] === rank ) {
				this._rank = rank;
				return;
			}
		}
		throw new Error( 'Can not set unknown Statement rank "' + rank + '"' );
	},

	/**
	 * @param {*} statement
	 * @return {boolean}
	 */
	equals: function( statement ) {
		return statement === this
			|| statement instanceof SELF
				&& this._claim.equals( statement.getClaim() )
				&& this._references.equals( statement.getReferences() )
				&& this._rank === statement.getRank();
	}
} );

/**
 * Rank enum.
 * @property {Object}
 * @static
 */
SELF.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

module.exports = SELF;

}( jQuery ) );
