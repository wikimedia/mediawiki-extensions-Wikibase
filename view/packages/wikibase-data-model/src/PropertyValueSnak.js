( function( dv, util ) {
'use strict';

var PARENT = require( './Snak.js' );

/**
 * Snak occupying a specific value.
 * @class PropertyValueSnak
 * @extends Snak
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {dataValues.DataValue} value
 * @param {string|null} [hash=null]
 *
 * @throws {Error} value is not a dataValues.DataValue instance.
 */
var SELF = util.inherit(
	'WbDataModelPropertyValueSnak',
	PARENT,
	function( propertyId, value, hash ) {
		if( !( value instanceof dv.DataValue ) ) {
			throw new Error( 'The value has to be an instance of dataValues.DataValue' );
		}
		PARENT.call( this, propertyId, hash );
		this._value = value;
	},
{
	/**
	 * @property {dataValues.DataValue}
	 * @private
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
	 * @inheritdoc
	 */
	equals: function( snak ) {
		return PARENT.prototype.equals.call( this, snak ) && this._value.equals( snak.getValue() );
	}
} );

/**
 * @inheritdoc
 * @property {string} [TYPE='value']
 * @static
 */
SELF.TYPE = 'value';

module.exports = SELF;

}( dataValues, util ) );
