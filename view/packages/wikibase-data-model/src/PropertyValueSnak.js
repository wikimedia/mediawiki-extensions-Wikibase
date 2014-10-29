/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( wb, dv, util ) {
'use strict';

var PARENT = wb.datamodel.Snak;

/**
 * @constructor
 * @extends wikibase.datamodel.Snak
 * @since 0.3
 *
 * @param {string} propertyId
 * @param {dataValues.DataValue} value
 */
var SELF = wb.datamodel.PropertyValueSnak = util.inherit(
	'WbDataModelPropertyValueSnak',
	PARENT,
	function( propertyId, value ) {
		if( !( value instanceof dv.DataValue ) ) {
			throw new Error( 'The value has to be an instance of dataValues.DataValue' );
		}
		PARENT.call( this, propertyId );
		this._value = value;
	},
{
	/**
	 * @type {dataValues.DataValue}
	 */
	_value: null,

	/**
	 * Returns the Snak object's value in form of a DataValue object.
	 *
	 * @return {dataValues.DataValue}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @see wikibase.datamodel.Snak.equals
	 */
	equals: function( snak ) {
		return PARENT.prototype.equals.call( this, snak ) && this._value.equals( snak.getValue() );
	}
} );

/**
 * @see wikibase.datamodel.Snak.TYPE
 */
SELF.TYPE = 'value';

}( wikibase, dataValues, util ) );
