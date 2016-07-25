( function( wb, $ ) {
'use strict';

/**
 * Combination of a claim, a rank and references.
 * @class wikibase.datamodel.Statement
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.Claim} claim
 * @param {wikibase.datamodel.ReferenceList|null} [references=new wikibase.datamodel.ReferenceList()]
 * @param {number} [rank=wikibase.datamodel.Statement.RANK.NORMAL]
 */
var SELF = wb.datamodel.Statement = function WbDataModelStatement( claim, references, rank ) {
	this.setClaim( claim );
	this.setReferences( references || new wb.datamodel.ReferenceList() );
	this.setRank( rank === undefined ? wb.datamodel.Statement.RANK.NORMAL : rank );
};

/**
 * @class wikibase.datamodel.Statement
 */
$.extend( SELF.prototype, {
	/**
	 * @property {wikibase.datamodel.Claim}
	 * @private
	 */
	_claim: null,

	/**
	 * @property {wikibase.datamodel.ReferenceList}
	 * @private
	 */
	_references: null,

	/**
	 * @see wikibase.datamodel.Statement.RANK
	 * @property {number}
	 * @private
	 */
	_rank: null,

	/**
	 * @return {wikibase.datamodel.Claim}
	 */
	getClaim: function() {
		return this._claim;
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 *
	 * @throws {Error} if claim is not a Claim instance.
	 */
	setClaim: function( claim ) {
		if( !( claim instanceof wb.datamodel.Claim ) ) {
			throw new Error( 'Claim needs to be an instance of wikibase.datamodel.Claim' );
		}
		this._claim = claim;
	},

	/**
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	getReferences: function() {
		return this._references;
	},

	/**
	 * @param {wikibase.datamodel.ReferenceList} references
	 *
	 * @throws {Error} if references is not a ReferenceList instance.
	 */
	setReferences: function( references ) {
		if( !( references instanceof wb.datamodel.ReferenceList ) ) {
			throw new Error( 'References have to be supplied in a ReferenceList' );
		}
		this._references = references;
	},

	/**
	 * @see wikibase.datamodel.Statement.RANK
	 *
	 * @return {number}
	 */
	getRank: function() {
		return this._rank;
	},

	/**
	 * @see wikibase.datamodel.Statement.RANK
	 *
	 * @param {number} rank
	 *
	 * @throws {Error} if rank is not defined in wikibase.datamodel.Statement.RANK.
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

}( wikibase, jQuery ) );
