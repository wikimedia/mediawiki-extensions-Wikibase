/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Unordered set of StatementGroup objects.
 * @constructor
 * @since 0.4
 *
 * @param {wikibase.datamodel.StatementGroup[]} [statementGroups]
 */
var SELF = wb.datamodel.StatementGroupList
	= function WbDataModelStatementGroupList( statementGroups ) {

	statementGroups = statementGroups || [];

	this._groups = {};
	this.length = 0;

	for( var i = 0; i < statementGroups.length; i++ ) {
		if( !statementGroups[i] instanceof wb.datamodel.StatementGroup ) {
			throw new Error( 'StatementGroupList may contain StatementGroup instances only' );
		}

		if( this._groups[statementGroups[i].getPropertyId()] ) {
			throw new Error( 'There may only be one StatementGroup per property id' );
		}

		this.setGroup( statementGroups[i] );
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
	 * @return {wikibase.datamodel.StatementGroup|null}
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
	 * @param {wikibase.datamodel.StatementGroup} statementGroup
	 */
	setGroup: function( statementGroup ) {
		var propertyId = statementGroup.getPropertyId();

		if( statementGroup.isEmpty() ) {
			this.removeByPropertyId( propertyId );
			return;
		}

		if( !this._groups[propertyId] ) {
			this.length++;
		}

		this._groups[propertyId] = statementGroup;
	},

	/**
	 * @return {boolean}
	 */
	isEmpty: function() {
		return this.length === 0;
	},

	/**
	 * @param {*} statementGroupList
	 * @return {boolean}
	 */
	equals: function( statementGroupList ) {
		if( statementGroupList === this ) {
			return true;
		} else if(
			!( statementGroupList instanceof SELF ) || this.length !== statementGroupList.length
		) {
			return false;
		}

		for( var propertyId in this._groups ) {
			if( !statementGroupList.hasGroup( this._groups[propertyId] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * @param {wikibase.datamodel.StatementGroup} statementGroup
	 * @return {boolean}
	 */
	hasGroup: function( statementGroup ) {
		var propertyId = statementGroup.getPropertyId();
		return this._groups[propertyId] && this._groups[propertyId].equals( statementGroup );
	}

} );

}( wikibase, jQuery ) );
