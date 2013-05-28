/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv ) {
'use strict';

var PARENT = wb.Snak,
	constructor = function( propertyId, value ) {
		if( !( value instanceof dv.DataValue ) ) {
			throw new Error( 'The value has to be an instance of dataValues.DataValue' );
		}
		PARENT.call( this, propertyId );
		this._value = value;
	};

/**
 * Represents a Wikibase PropertyValueSnak in JavaScript.
 * @constructor
 * @extends wb.Snak
 * @since 0.2
 * @see https://meta.wikimedia.org/wiki/Wikidata/Data_model#PropertyValueSnak
 *
 * @param {Number} propertyId
 * @param {dv.DataValue} value
 */
var SELF = wb.PropertyValueSnak = wb.utilities.inherit( 'WbPropertyValueSnak', PARENT, constructor, {
	/**
	 * @type dv.DataValue
	 */
	_value: null,

	/**
	 * Returns the Snaks data value.
	 *
	 * @return {dv.DataValue}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @see wb.Snak.equals
	 */
	equals: function( snak ) {
		// Snaks are equal if basic stuff (id, type) are equal...
		var equal = PARENT.prototype.equals.call( this, snak );

		if( !equal ) {
			return false;
		}
		// ... plus, the actual value has to be equal:
		return this._value.equals( snak.getValue() );
	},

	/**
	 * @see wb.Snak.toJSON
	 * TODO: implement this as a wb.serialization.Serializer
	 */
	toJSON: function() {
		var json = this.toMap();

		json.datavalue = {
			type: json.datavalue.getType(),
			value: json.datavalue.toJSON()
		};

		return json;
	},

	/**
	 * @see wb.Snak.toMap
	 */
	toMap: function() {
		var map = PARENT.prototype.toMap.call( this );

		map.datavalue = this.getValue();
		return map;
	}
} );

/**
 * @see wb.Snak.TYPE
 */
SELF.TYPE = 'value';

}( wikibase, dataValues ) );
