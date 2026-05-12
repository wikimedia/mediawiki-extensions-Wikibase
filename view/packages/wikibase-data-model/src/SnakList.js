( function( $ ) {
'use strict';

var PARENT = require( './List.js' ),
	Snak = require( './Snak.js' );

/**
 * List of Snak objects.
 * @class SnakList
 * @extends List
 * @abstract
 * @since 0.3
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {Snak[]} [snaks=[]]
 */
module.exports = util.inherit( 'WbDataModelSnakList', PARENT, function( snaks ) {
	PARENT.call( this, Snak, snaks );
}, {
	/**
	 * Returns a SnakList with the snaks featuring a specific property id.
	 *
	 * @param {string} propertyId
	 * @return {SnakList}
	 * @private
	 */
	getFilteredSnakList: function( propertyId ) {
		if( !propertyId ) {
			throw new Error( 'Can not filter with no propertyId.' );
		}

		var filteredSnakList = new this.constructor();

		this.each( function( i, snak ) {
			if( snak.getPropertyId() === propertyId ) {
				filteredSnakList.addItem( snak );
			}
		} );

		return filteredSnakList;
	},

	/**
	 * Returns a list of SnakList objects, each of them grouped by the property used by the snaks.
	 *
	 * @return {SnakList[]}
	 */
	getGroupedSnakLists: function() {
		var groupedSnakLists = [],
			propertyIds = this.getPropertyOrder();

		for( var i = 0; i < propertyIds.length; i++ ) {
			groupedSnakLists.push( this.getFilteredSnakList( propertyIds[i] ) );
		}

		return groupedSnakLists;
	},

	/**
	 * Adds the Snaks of another SnakList to this SnakList.
	 *
	 * @param {SnakList|null} [snakList=null]
	 */
	merge: function( snakList ) {
		if( !snakList ) {
			return;
		}

		var self = this;

		snakList.each( function( i, snak ) {
			if( !self.hasItem( snak ) ) {
				self._items.push( snak );
				self.length++;
			}
		} );
	},

	/**
	 * Returns a list of property ids representing the order of the Snaks grouped by property.
	 *
	 * @return {string[]}
	 */
	getPropertyOrder: function() {
		var propertyIds = [];

		this.each( function( i, snak ) {
			var propertyId = snak.getPropertyId();

			if( $.inArray( propertyId, propertyIds ) === -1 ) {
				propertyIds.push( propertyId );
			}
		} );

		return propertyIds;
	}
} );

}( jQuery ) );
