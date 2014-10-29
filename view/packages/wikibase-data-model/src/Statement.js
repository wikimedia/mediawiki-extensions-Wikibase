/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * @constructor
 * @since 0.3
 *
 * @param {wikibase.datamodel.Claim} claim
 * @param {wikibase.datamodel.ReferenceList|null} [references]
 * @param {number} [rank]
 */
var SELF = wb.datamodel.Statement = function WbDataModelStatement( claim, references, rank ) {
	this.setClaim( claim );
	this.setReferences( references || new wb.datamodel.ReferenceList() );
	this.setRank( rank === undefined ? wb.datamodel.Statement.RANK.NORMAL : rank );
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.Claim}
	 */
	_claim: null,

	/**
	 * @type {wikibase.datamodel.ReferenceList}
	 */
	_references: null,

	/**
	 * @see wikibase.datamodel.Statement.RANK
	 * @type {number}
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
	 */
	setReferences: function( references ) {
		if( !( references instanceof wb.datamodel.ReferenceList ) ) {
			throw new Error( 'References have to be supplied in a ReferenceList' );
		}
		this._references = references;
	},

	/**
	 * @return {number} (see wikibase.datamodel.Statement.RANK)
	 */
	getRank: function() {
		return this._rank;
	},

	/**
	 * @param {number} rank (see wikibase.datamodel.Statement.RANK)
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
 * @type {Object}
 */
SELF.RANK = {
	PREFERRED: 2,
	NORMAL: 1,
	DEPRECATED: 0
};

}( wikibase, jQuery ) );
