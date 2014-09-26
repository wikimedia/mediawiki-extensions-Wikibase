/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Ordered list of claims each featuring the same property.
 * @constructor
 * @since 0.4
 *
 * @param {string} propertyId
 * @param {wikibase.datamodel.ClaimList} claimList
 */
var SELF = wb.datamodel.ClaimGroup = function WbDataModelClaimGroup( propertyId, claimList ) {
	if( typeof propertyId !== 'string' ) {
		throw new Error( 'propertyId needs to be a string' );
	}

	claimList = claimList || new wb.datamodel.ClaimList();

	this._propertyId = propertyId;
	this.setClaimList( claimList );
};

$.extend( SELF.prototype, {
	/**
	 * @type {string}
	 */
	_propertyId: null,

	/**
	 * @type {wikibase.datamodel.ClaimList}
	 */
	_claimList: null,

	/**
	 * @return {string}
	 */
	getPropertyId: function() {
		return this._propertyId;
	},

	/**
	 * @return {wikibase.datamodel.ClaimList}
	 */
	getClaimList: function() {
		// Do not allow altering the encapsulated ClaimList.
		return new wb.datamodel.ClaimList( this._claimList.toArray() );
	},

	/**
	 * @param {wikibase.datamodel.ClaimList} claimList
	 */
	setClaimList: function( claimList ) {
		var propertyIds = claimList.getPropertyIds();

		for( var i = 0; i < propertyIds.length; i++ ) {
			if( propertyIds[i] !== this._propertyId ) {
				throw new Error(
					'Mismatching property id: Expected ' + this._propertyId + ' received '
						+ propertyIds[i]
				);
			}
		}

		this._claimList = claimList;
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 * @return {boolean}
	 */
	hasClaim: function( claim ) {
		return this._claimList.hasItem( claim );
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 */
	addClaim: function( claim ) {
		if( claim.getMainSnak().getPropertyId() !== this._propertyId ) {
			throw new Error(
				'Mismatching property id: Expected ' + this._propertyId + ' received '
					+ claim.getMainSnak().getPropertyId()
			);
		}
		this._claimList.addItem( claim );
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 */
	removeClaim: function( claim ) {
		this._claimList.removeItem( claim );
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this._claimList.isEmpty();
	},

	/**
	 * @param {*} claimGroup
	 * @return {boolean}
	 */
	equals: function( claimGroup ) {
		return claimGroup === this
			|| claimGroup instanceof SELF
			&& this._propertyId === claimGroup.getPropertyId()
			&& this._claimList.equals( claimGroup.getClaimList() );
	}

} );

}( wikibase, jQuery ) );
