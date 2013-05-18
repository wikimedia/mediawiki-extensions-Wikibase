/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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
		if( snakList.constructor !== this.constructor ||
			snakList.length !== this.length
		) {
			return false;
		}
		for( var i in this._snaks ) {
			if( !snakList.hasSnak( this._snaks[i] ) ) {
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
 * @param {String} json
 * @return wb.Snak|null
 */
SELF.newFromJSON = function( json ) {
	var snaksList = new SELF();

	$.each( json, function( propertyId, snaksPerProperty ) {
		$.each( snaksPerProperty, function( i, snakJson ) {
			snaksList.addSnak( wb.Snak.newFromJSON( snakJson ) );
		} );
	} );

	return snaksList;
};

}( wikibase, jQuery ) );
