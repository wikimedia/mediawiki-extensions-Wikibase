( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a data value representing a boolean.
 * @class dataValues.BoolValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 *
 * @param {boolean} value
 *
 * @throws {Error} if value is not of type boolean.
 */
var SELF = dv.BoolValue = util.inherit( 'DvBoolValue', PARENT, function( value ) {
	if( typeof value !== 'boolean' ) {
		throw new Error( 'A boolean value has to be given' );
	}
	this._value = value;
}, {
	/**
	 * @property {boolean}
	 * @private
	 */
	_value: null,

	/**
	 * @inheritdoc
	 *
	 * @return {number}
	 */
	getSortKey: function() {
		return this._value ? 1 : 0;
	},

	/**
	 * @inheritdoc
	 *
	 * @return {boolean}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.BoolValue ) ) {
			return false;
		}

		return this.getValue() === value.getValue();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {boolean}
	 */
	toJSON: function() {
		return this._value;
	}

} );

/**
 * @inheritdoc
 * @static
 *
 * @return {dataValues.BoolValue}
 */
SELF.newFromJSON = function( json ) {
	return new dv.BoolValue( json );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='boolean']
 * @static
 */
SELF.TYPE = 'boolean';

dv.registerDataValue( SELF );

}( dataValues, util ) );
