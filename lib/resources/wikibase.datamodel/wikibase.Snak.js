/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, dv, $ ) {
'use strict';

/**
 * Represents a Wikibase Snak in JavaScript.
 * @constructor
 * @abstract
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @param {String} propertyId
 */
var SELF = wb.Snak = function WbSnak( propertyId ) {
	// check whether the Snak has a type, doesn't make sense to create an instance of wb.Snak!
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract Snak of no specific type' );
	}
	if( !propertyId ) {
		throw new Error( 'Property ID is required for constructing new Snak' );
	}
	this._propertyId = propertyId;
};

/**
 * String to identify this type of Snak
 * @since 0.3
 * @type String
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {
	/**
	 * @type Number
	 */
	_propertyId: null,

	/**
	 * Returns what type of Snak this is.
	 * @since 0.3

	 * @return String
	 */
	getType: function() {
		return this.constructor.TYPE;
	},

	/**
	 * Returns the ID of the property entity the Snak relates to.
	 * @since 0.3
	 *
	 * @return string
	 */
	getPropertyId: function() {
		return this._propertyId;
	},

	/**
	 * Returns whether this Snak is equal to another Snak. This means that their property and type
	 * are the same, as well as any other attributes they might have depending on their Snak type.
	 *
	 * @param {wb.Snak|*} that
	 * @return {Boolean}
	 */
	equals: function( that ) {
		if( !( that instanceof this.constructor ) ) {
			return false;
		}

		return that === this ||
			(
				this.getPropertyId() === that.getPropertyId() &&
				this.getType() === that.getType()
			);
	},

	/**
	 * Returns a simple JSON structure representing this Snak.
	 * @since 0.3
	 *
	 * TODO: implement this as a wb.serialization.Serializer
	 *
	 * @return Object
	 */
	toJSON: function() {
		return this.toMap();
	},

	/**
	 * Returns a plain Object representing this Snak. Similar to toJSON(), containing the same
	 * fields but the values within the fields will not be serialized to JSON in the Object returned
	 * by this function.
	 *
	 * @since 0.4
	 *
	 * @return {Object} Object with 'snaktype' and 'property' fields and possibly others, depending
	 *         on the Snak type.
	 */
	toMap: function() {
		return {
			snaktype: this.getType(),
			property: this.getPropertyId()
		};
	}
} );

// TODO: make newFromJSON and newFromMap abstract factories with registration for new Snak types!
/**
 * Creates a new Snak Object from a given JSON structure.
 *
 * @param {String} json
 * @return wb.Snak|null
 */
SELF.newFromJSON = function( json ) {
	// don't alter given Object in case of 'value' Snak by copying structure into new Object
	var map = $.extend( {}, json );

	if( json.snaktype === 'value' ) {
		map.datavalue = dv.newDataValue(
			json.datavalue.type,
			json.datavalue.value
		);
	}
	return SELF.newFromMap( map );
};

/**
 * Creates a new Snak Object from a given Object with certain keys and values, what an actual Snak
 * would return when calling its toMap().
 *
 * @since 0.4
 *
 * @param {Object} map Requires at least 'snaktype' and 'property' fields.
 * @return wb.Snak|null
 */
SELF.newFromMap = function( map ) {
	switch( map.snaktype ) {
		case 'value':
			return new wb.PropertyValueSnak( map.property, map.datavalue );
		case 'novalue':
			return new wb.PropertyNoValueSnak( map.property );
		case 'somevalue':
			return new wb.PropertySomeValueSnak( map.property );
		default:
			return null;
	}
};

}( wikibase, dataValues, jQuery ) );
