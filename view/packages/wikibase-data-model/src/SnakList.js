/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

var PARENT = wb.datamodel.List;

/**
 * @constructor
 * @abstract
 * @since 0.3
 *
 * @param {wikibase.datamodel.Snak[]} [snaks]
 */
wb.datamodel.SnakList = util.inherit( 'WbDataModelSnakList', PARENT, function( snaks ) {
	PARENT.call( this, wikibase.datamodel.Snak, snaks );
}, {
	/**
	 * Returns a SnakList with the snaks featuring a specific property id. If the property id
	 * parameter is omitted, a copy of the whole SnakList object is returned.
	 *
	 * @param {string} [propertyId]
	 * @return {wikibase.datamodel.SnakList}
	 */
	getFilteredSnakList: function( propertyId ) {
		if( !propertyId ) {
			return new wb.datamodel.SnakList( $.merge( [], this._items ) );
		}

		var filteredQualifiers = new wb.datamodel.SnakList();

		this.each( function( i, snak ) {
			if( snak.getPropertyId() === propertyId ) {
				filteredQualifiers.addItem( snak );
			}
		} );

		return filteredQualifiers;
	},

	/**
	 * Returns a list of SnakList objects, each of them grouped by the property used by the snaks.
	 *
	 * @return {wikibase.datamodel.SnakList[]}
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
	 * @param {wikibase.datamodel.SnakList} snakList
	 */
	merge: function( snakList ) {
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
	},

	/**
	 * Returns the indices of the snak list where a certain snak may be moved to. A snak may be
	 * moved within its property group. It may also be moved to the slots between property groups
	 * which involves moving the whole property group the snak belongs to.
	 *
	 * @param {wikibase.datamodel.Snak} snak
	 * @return {number[]}
	 */
	getValidMoveIndices: function( snak ) {
		var self = this,
			indices = [],
			isGroupLast = false;

		this.each( function( i, snakListSnak ) {
			if( snakListSnak.getPropertyId() === snak.getPropertyId() ) {
				// Detect slots within the snak's property group.
				if( snakListSnak !== snak ) {
					indices.push( i );
				} else {
					var nextSnak = self._items[i + 1];
					if( nextSnak && nextSnak.getPropertyId() !== snak.getPropertyId() ) {
						// Snak is the last of its group.
						isGroupLast = true;
					}
				}
			} else {
				// Detect slots between property groups.
				var previousSnak = self._items[i - 1],
					isNewPropertyGroup = (
						i !== 0
						&& snakListSnak.getPropertyId() !== previousSnak.getPropertyId()
					);

				if(
					// Since this snak's property group is not at the top of the snak list, the
					// snak (with its group) may always be moved to the top:
					i === 0
					// The snak (with its group) may always be moved to between groups except to
					// adjacent slots between property groups since the snak's property group would
					// in fact not be moved.
					|| isNewPropertyGroup && previousSnak.getPropertyId() !== snak.getPropertyId()
				) {
					indices.push( i );
				}
			}
		} );

		// Allow moving to last position if snak is not at the end already:
		if( snak !== this._items[this._items.length - 1] ) {
			indices.push( this._items.length );
		}

		return indices;
	},

	/**
	 * Moves a SnakList's Snak to a new index.
	 *
	 * @param {wikibase.datamodel.Snak} snak Snak to move within the list.
	 * @param {number} toIndex
	 * @return {wikibase.datamodel.SnakList} This SnakList object.
	 *
	 * @throws {Error} if snak is not allowed to be moved to toIndex.
	 */
	move: function( snak, toIndex ) {
		if( this.indexOf( snak ) === toIndex ) {
			return this;
		}

		var validIndices = this.getValidMoveIndices( snak );

		if( $.inArray( toIndex, validIndices ) === -1 ) {
			throw new Error( 'Tried to move snak to index ' + toIndex + ' but only the following '
				+ 'indices are allowed: ' + validIndices.join( ', ' ) );
		}

		var previousSnak = this._items[toIndex -1],
			nextSnak = this._items[toIndex + 1],
			insertBefore = this._items[toIndex];

		if(
			previousSnak && previousSnak.getPropertyId() === snak.getPropertyId()
			|| nextSnak && nextSnak.getPropertyId() === snak.getPropertyId()
		) {
			// Moving snak within its property group.
			this._items.splice( this.indexOf( snak ), 1 );

			if( insertBefore ) {
				this._items.splice( toIndex, 0, snak );
			} else {
				this._items.push( snak );
			}
		} else {
			// Moving the whole snak group.
			var groupedSnaks = [];

			for( var i = 0; i < this._items.length; i++ ) {
				if( this._items[i].getPropertyId() === snak.getPropertyId() ) {
					groupedSnaks.push( this._items[i] );
				}
			}

			for( i = 0; i < groupedSnaks.length; i++ ) {
				this._items.splice( this.indexOf( groupedSnaks[i] ), 1 );
				if( insertBefore ) {
					this._items.splice( this.indexOf( insertBefore ), 0, groupedSnaks[i] );
				} else {
					this._items.push( groupedSnaks[i] );
				}
			}
		}

		return this;
	},

	/**
	 * Moves a snak towards the top of the snak list by one step.
	 *
	 * @param {wikibase.datamodel.Snak} snak
	 * @return {wikibase.datamodel.SnakList} This SnakList object.
	 */
	moveUp: function( snak ) {
		var index = this.indexOf( snak ),
			validIndices = this.getValidMoveIndices( snak );

		for( var i = validIndices.length - 1; i >= 0; i-- ) {
			if( validIndices[i] < index ) {
				this.move( snak, validIndices[i] );
				break;
			}
		}

		return this;
	},

	/**
	 * Moves a snak towards the bottom of the snak list by one step.
	 *
	 * @param {wikibase.datamodel.Snak} snak
	 * @return {wikibase.datamodel.SnakList} This SnakList object.
	 */
	moveDown: function( snak ) {
		var index = this.indexOf( snak ),
			validIndices = this.getValidMoveIndices( snak );

		for( var i = 0; i < validIndices.length; i++ ) {
			if( validIndices[i] > index ) {
				this.move( snak, validIndices[i] );
				break;
			}
		}

		return this;
	}
} );

}( wikibase, jQuery ) );
