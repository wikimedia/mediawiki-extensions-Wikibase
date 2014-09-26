/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Ordered set of Claim objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.Claim[]} [claims]
 */
var SELF = wb.datamodel.ClaimList = function WbDataModelClaimList( claims ) {
	claims = claims || [];

	this._claims = [];
	this.length = 0;

	for( var i = 0; i < claims.length; i++ ) {
		this.addClaim( claims[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.Claim[]}
	 */
	_claims: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @return {wikibase.datamodel.Claim[]}
	 */
	toArray: function() {
		return this._claims.slice();
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 * @return {boolean}
	 */
	hasClaim: function( claim ) {
		var guid = claim.getGuid();

		if( !guid ) {
			return false;
		}

		for( var i = 0; i < this._claims.length; i++ ) {
			if( guid === this._claims[i].getGuid() && claim.equals( this._claims[i] ) ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 */
	addClaim: function( claim ) {
		if( !( claim instanceof wb.datamodel.Claim ) ) {
			throw new Error( 'ClaimList may contain Claim instances only' );
		}

		this._claims.push( claim );
		this.length++;
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 */
	removeClaim: function( claim ) {
		for( var i = 0; i < this._claims.length; i++ ) {
			if( this._claims[i].getGuid() === claim.getGuid() && this._claims[i].equals( claim ) ) {
				this._claims.splice( i, 1 );
				this.length--;
				return;
			}
		}
		throw new Error( 'Trying to remove a non-existing claim' );
	},

	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var i = 0; i < this._claims.length; i++ ) {
			var propertyId = this._claims[i].getMainSnak().getPropertyId();
			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		}

		return propertyIds;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} claimList
	 * @return {boolean}
	 */
	equals: function( claimList ) {
		if( claimList === this ) {
			return true;
		} else if( !( claimList instanceof SELF ) || this.length !== claimList.length ) {
			return false;
		}

		for( var i = 0; i < this._claims.length; i++ ) {
			if( claimList.indexOf( this._claims[i] ) !== i ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.Claim} claim
	 * @return {number}
	 */
	indexOf: function( claim ) {
		for( var i = 0; i < this._claims.length; i++ ) {
			if( this._claims[i].getGuid() === claim.getGuid() && this._claims[i].equals( claim ) ) {
				return i;
			}
		}
		return -1;
	}

} );

}( wikibase, jQuery ) );
