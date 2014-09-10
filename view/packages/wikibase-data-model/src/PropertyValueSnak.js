/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv, util ) {
'use strict';

var PARENT = wb.datamodel.Snak,
	constructor = function( propertyId, value ) {
		if( !( value instanceof dv.DataValue ) ) {
			throw new Error( 'The value has to be an instance of dataValues.DataValue' );
		}
		PARENT.call( this, propertyId );
		this._value = value;
	};

/**
 * Represents a Wikibase PropertyValueSnak.
 * @constructor
 * @extends wb.datamodel.Snak
 * @since 0.3
 *
 * @param {string} propertyId
 * @param {dv.DataValue} value
 */
var SELF = wb.datamodel.PropertyValueSnak = util.inherit( 'WbPropertyValueSnak', PARENT, constructor, {
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
	 * @see wb.datamodel.Snak.equals
	 */
	equals: function( snak ) {
		// Snaks are equal if basic stuff (id, type) are equal...
		var equal = PARENT.prototype.equals.call( this, snak );

		if( !equal ) {
			return false;
		}
		// ... plus, the actual value has to be equal:
		return this._value.equals( snak.getValue() );
	}
} );

/**
 * @see wb.datamodel.Snak.TYPE
 */
SELF.TYPE = 'value';

}( wikibase, dataValues, util ) );
