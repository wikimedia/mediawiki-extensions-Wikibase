/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv, $, undefined ) {
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
wb.PropertyValueSnak = wb.utilities.inherit( PARENT, constructor, {
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
	 * @see wb.Snak.toJSON
	 */
	toJSON: function() {
		var json = PARENT.prototype.toJSON.call( this );

		json.datavalue = {
			type: this.getValue().getType(),
			value: this.getValue().toJSON()
		};

		return json;
	}
} );

/**
 * @see wb.Snak.TYPE
 */
wb.PropertyValueSnak.TYPE = 'value';

}( wikibase, dataValues, jQuery ) );