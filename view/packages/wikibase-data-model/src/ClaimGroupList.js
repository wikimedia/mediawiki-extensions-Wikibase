/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set of ClaimGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.ClaimGroup[]} [claimGroups]
 */
var SELF = wb.datamodel.ClaimGroupList = function WbDataModelClaimGroupList( claimGroups ) {
	claimGroups = claimGroups || [];

	this._groups = {};
	this.length = 0;

	for( var i = 0; i < claimGroups.length; i++ ) {
		if( !claimGroups[i] instanceof wb.datamodel.ClaimGroup ) {
			throw new Error( 'ClaimGroupList may contain ClaimGroup instances only' );
		}

		if( this._groups[claimGroups[i].getPropertyId()] ) {
			throw new Error( 'There may only be one ClaimGroup per property id' );
		}

		this.setGroup( claimGroups[i] );
	}
};

$.extend( SELF.prototype, {
	/**
	 * @type {Object}
	 */
	_groups: null,

	/**
	 * @type {number}
	 */
	length: 0,

	/**
	 * @return {string[]}
	 */
	getPropertyIds: function() {
		var propertyIds = [];

		for( var propertyId in this._groups ) {
			propertyIds.push( propertyId );
		}

		return propertyIds;
	},

	/**
	 * @param {string} propertyId
	 * @return {wikibase.datamodel.ClaimGroup|null}
	 */
	getByPropertyId: function( propertyId ) {
		return this._groups[propertyId] || null;
	},

	/**
	 * @param {string} propertyId
	 */
	removeByPropertyId: function( propertyId ) {
		if( this._groups[propertyId] ) {
			this.length--;
		}
		delete this._groups[propertyId];
	},

	/**
	 * @param {string} propertyId
	 * @return {boolean}
	 */
	hasGroupForPropertyId: function( propertyId ) {
		return !!this._groups[propertyId];
	},

	/**
	 * @param {wikibase.datamodel.ClaimGroup} claimGroup
	 */
	setGroup: function( claimGroup ) {
		var propertyId = claimGroup.getPropertyId();

		if( claimGroup.isEmpty() ) {
			this.removeByPropertyId( propertyId );
			return;
		}

		if( !this._groups[propertyId] ) {
			this.length++;
		}

		this._groups[propertyId] = claimGroup;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} claimGroupList
	 * @return {boolean}
	 */
	equals: function( claimGroupList ) {
		if( claimGroupList === this ) {
			return true;
		} else if ( !( claimGroupList instanceof SELF ) || this.length !== claimGroupList.length ) {
			return false;
		}

		for( var propertyId in this._groups ) {
			if( !claimGroupList.hasGroup( this._groups[propertyId] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.ClaimGroup} claimGroup
	 * @return {boolean}
	 */
	hasGroup: function( claimGroup ) {
		var propertyId = claimGroup.getPropertyId();
		return this._groups[propertyId] && this._groups[propertyId].equals( claimGroup );
	}

} );

}( wikibase, jQuery ) );
