/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
'use strict';

/**
 * Container for a list of Snaks. Each snak within the list in unique, Snaks considered as equal
 * will not be added to the list a second time.
 *
 * @constructor
 * @abstract
 * @since 0.4
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @param {wb.Snak[]|wb.Snak|wb.SnakList} [snaks] One or more Snaks in the list initially.
 */
var SELF = wb.SnakList = function WbSnakList( snaks ) {
	this._snaks = [];
	this.length = 0;

	if( $.isArray( snaks ) ) {
		for( var i in snaks ) {
			this.addSnak( snaks[i] );
		}
	}
	else if( snaks instanceof wb.SnakList ) {
		this._snaks = snaks.toArray();
		this.length = this._snaks.length;
	}
	else if( snaks instanceof wb.Snak ) {
		this.addSnak( snaks );
	}
	else if( snaks !== undefined ) {
		throw new Error( 'Unknown first argument in SnakList constructor' );
	}
};

$.extend( SELF.prototype, {
	/**
	 * Number of snaks in the list currently.
	 * @type number
	 */
	length: 0,

	/**
	 * List of snaks for keeping track over Snaks internally.
	 * @type wb.Snak[]
	 */
	_snaks: null,

	/**
	 * Will add a given Snak to the list of Snaks. If an equal Snak is in the list already, the
	 * Snak will not be added.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Snak} snak
	 * @return boolean Whether Snak was not yet in the list and therefore added.
	 */
	addSnak: function( snak ) {
		if( !( snak instanceof wb.Snak ) ) {
			throw new Error( 'No Snak given which could be added to the Snak list' );
		}
		// if an equal Snak is in the list already, don't add it again!
		if( !this.hasSnak( snak ) ) {
			this._snaks.push( snak );
			this.length++;
			return true;
		}
		return false;
	},

	/**
	 * Removes a given Snak from the list.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Snak} snak
	 * @return boolean Whether Snak was in the list and therefore removed.
	 */
	removeSnak: function( snak ) {
		// if an equal Snak is in the list already, don't add it again
		for( var i in this._snaks ) {
			if( this._snaks[i].equals( snak ) ) {
				// JS will not leave 'gaps' in the array, so no worries about the each()'s
				// callback's index.
				this._snaks.splice( i, 1 );
				this.length--;
				return true;
			}
		}
		return false;
	},

	/**
	 * Returns a snak list with the snaks that feature the specified property id. If the property id
	 * parameter is omitted, a copy of the whole SnakList object is returned.
	 * @since 0.4
	 *
	 * @param {string} [propertyId]
	 * @return {wikibase.SnakList}
	 */
	getFilteredSnakList: function( propertyId ) {
		if( !propertyId ) {
			return this.constructor.newFromJSON( this.toJSON() );
		}

		var filteredQualifiers = new wb.SnakList();

		this.each( function( i, snak ) {
			if( snak.getPropertyId() === propertyId ) {
				filteredQualifiers.addSnak( snak );
			}
		} );

		return filteredQualifiers;
	},

	/**
	 * Returns whether the list contains a Snak equal to a given one.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Snak} snak
	 * @return boolean
	 */
	hasSnak: function( snak ) {
		for( var i in this._snaks ) {
			if( this._snaks[i].equals( snak ) ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * Will return whether a given Snak list equals this one.
	 *
	 * @since 0.4
	 *
	 * @param {wb.SnakList} snakList
	 * @return boolean
	 */
	equals: function( snakList ) {
		if( snakList.constructor !== this.constructor
			|| snakList.length !== this.length
		) {
			return false;
		}

		var otherSnaks = snakList.toArray();

		// Compare to other snak lists snaks considering order:
		for( var i = 0; i < otherSnaks.length; i++ ) {
			if( !this._snaks[i].equals( otherSnaks[i] ) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Iterates over all Snaks in the list, similar to jQuery.each.
	 *
	 * @since 0.4
	 *
	 * @param {Function} fn A callback, called for each Snak in the list. The callback can have
	 *        two parameters:
	 *        (1) {Number} index A continuous number, increased with each callback.
	 *        (2) {wb.Snak} snak A Snak from the list.
	 *        If false is returned by one of the callbacks, the iteration will stop. The context of
	 *        the callbacks will be the Snak object.
	 */
	each: function( fn ) {
		$.each.call( null, this._snaks, fn );
	},

	/**
	 * Adds the snaks of another snak list to this snak list.
	 * @since 0.4
	 *
	 * @param {wikibase.SnakList} snakList
	 */
	add: function( snakList ) {
		var self = this;

		snakList.each( function( i, snak ) {
			if( !self.hasSnak( snak ) ) {
				self._snaks.push( snak );
				self.length++;
			}
		} );
	},

	/**
	 * Returns a list of property ids representing the order of the snaks grouped by property.
	 *
	 * @return {string[]}
	 */
	getPropertyOrder: function() {
		var json = this.toJSON(),
			propertyIds = [];

		$.each( json, function( propertyId, snak ) {
			propertyIds.push( propertyId );
		} );

		return propertyIds;
	},

	/**
	 * Returns a snak's index within the snak list. Returns -1 when the snak could not be found.
	 * @since 0.4
	 *
	 * @param {wikibase.Snak} snak
	 * @return {number}
	 */
	indexOf: function( snak ) {
		for( var i = 0; i < this._snaks.length; i++ ) {
			if( this._snaks[i].equals( snak ) ) {
				return i;
			}
		}
		return -1;
	},

	/**
	 * Returns the indices of the snak list where a certain snak may be moved to. A snak may be
	 * moved within its property group. It may also be moved to the slots between property groups
	 * which involves moving the whole property group the snak belongs to.
	 * @since 0.4
	 *
	 * @param {wikibase.Snak} snak
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
					var nextSnak = self._snaks[i + 1];
					if( nextSnak && nextSnak.getPropertyId() !== snak.getPropertyId() ) {
						// Snak is the last of its group.
						isGroupLast = true;
					}
				}
			} else {
				// Detect slots between property groups.
				var previousSnak = self._snaks[i - 1],
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
		if( snak !== this._snaks[this._snaks.length - 1] ) {
			indices.push( this._snaks.length );
		}

		return indices;
	},

	/**
	 * Moves a snaklist's snak to a new index.
	 * @since 0.4
	 *
	 * @param {wikibase.Snak} snak Snak to move within the list.
	 * @param {number} toIndex
	 *
	 * @throws {Error} if snak is not allowed to be moved to toIndex.
	 */
	move: function( snak, toIndex ) {
		if( this.indexOf( snak ) === toIndex ) {
			return;
		}

		var validIndices = this.getValidMoveIndices( snak );

		if( $.inArray( toIndex, validIndices ) === -1 ) {
			throw new Error( 'Tried to move snak to index ' + toIndex + ' but only the following '
				+ 'indices are allowed: ' + validIndices.join( ', ' ) );
		}

		var previousSnak = this._snaks[toIndex -1],
			nextSnak = this._snaks[toIndex + 1],
			insertBefore = this._snaks[toIndex];

		if(
			previousSnak && previousSnak.getPropertyId() === snak.getPropertyId()
			|| nextSnak && nextSnak.getPropertyId() === snak.getPropertyId()
		) {
			// Moving snak within its property group.
			this._snaks.splice( this.indexOf( snak ), 1 );

			if( insertBefore ) {
				this._snaks.splice( toIndex, 0, snak );
			} else {
				this._snaks.push( snak );
			}
		} else {
			// Moving the whole snak group.
			var groupedSnaks = [];

			for( var i = 0; i < this._snaks.length; i++ ) {
				if( this._snaks[i].getPropertyId() === snak.getPropertyId() ) {
					groupedSnaks.push( this._snaks[i] );
				}
			}

			for( i = 0; i < groupedSnaks.length; i++ ) {
				this._snaks.splice( this.indexOf( groupedSnaks[i] ), 1 );
				if( insertBefore ) {
					this._snaks.splice( this.indexOf( insertBefore ), 0, groupedSnaks[i] );
				} else {
					this._snaks.push( groupedSnaks[i] );
				}
			}
		}

	},

	/**
	 * Moves a snak towards the top of the snak list by one step.
	 * @since 0.4
	 *
	 * @param {wikibase.Snak} snak
	 * @return {number} The snaks new index.
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

		return this.indexOf( snak );
	},

	/**
	 * Moves a snak towards the bottom of the snak list by one step.
	 * @since 0.4
	 *
	 * @param {wikibase.Snak} snak
	 * @return {number} The snak's new index.
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

		return this.indexOf( snak );
	},

	/**
	 * Returns a simple JSON structure representing this Snak. The structure will be a map, having
	 * property IDs as keys and an array of the Snak's JSON as values.
	 *
	 * @since 0.4
	 *
	 * TODO: implement this as a wb.serialization.Serializer
	 *
	 * @return Object
	 */
	toJSON: function() {
		var json = {};

		this.each( function( i, snak ) {
			var propertyId = snak.getPropertyId(),
				snakPlace = json[ propertyId ] || ( json[ propertyId ] = [] );

			snakPlace.push( snak.toJSON() );
		} );

		return json;
	},

	/**
	 * Returns all Snaks in this list as an Array of Snaks. Changes to the array will not modify
	 * the original list Object.
	 *
	 * @since 0.4
	 *
	 * @return wb.Snak[]
	 */
	toArray: function() {
		return this._snaks.slice(); // don't reveal internal array!
	}
} );

/**
 * Creates a new Snak Object from a given JSON structure.
 *
 * @param {string} json
 * @param {string[]} [order] List of property ids defining the order of the snaks grouped by
 *        property.
 * @return {wikibase.SnakList|null}
 */
SELF.newFromJSON = function( json, order ) {
	var snaksList = new SELF();

	if( !order ) {
		// No order specified: Just loop through the json object:
		$.each( json, function( propertyId, snaksPerProperty ) {
			$.each( snaksPerProperty, function( i, snakJson ) {
				snaksList.addSnak( wb.Snak.newFromJSON( snakJson ) );
			} );
		} );

	} else {
		// Check whether all property ids that are featured by snaks are specified in the order
		// list:
		$.each( json, function( propertyId, snakListJson ) {
			if( $.inArray( propertyId, order ) === -1 ) {
				throw new Error( 'Snak featuring the property id ' + propertyId + ' is not present '
					+ 'within list of property ids defined for ordering' );
			}
		} );

		// Add all snaks grouped by property according to the order specified via the "order"
		// parameter:
		for( var i = 0; i < order.length; i++ ) {
			if( !json[order[i]] ) {
				throw new Error( 'Trying to oder by property ' + order[i] + ' without any snak '
					+ ' featuring this property being present' );
			}

			for( var j = 0; j < json[order[i]].length; j++ ) {
				snaksList.addSnak( wb.Snak.newFromJSON( json[order[i]][j] ) );
			}
		}
	}

	return snaksList;
};

}( wikibase, jQuery ) );
