( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue;

/**
 * Constructor for creating a data value representing a string.
 * @class dataValues.StringValue
 * @extends dataValues.DataValue
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 *
 * @constructor
 *
 * @param {string} value
 *
 * @throws {Error} if value is not a string.
 */
var SELF = dv.StringValue = util.inherit( 'DvStringValue', PARENT, function( value ) {
	if( typeof value !== 'string' ) {
		throw new Error( 'A string value has to be given' );
	}
	this._value = value;
}, {
	/**
	 * @property {string}
	 * @private
	 */
	_value: null,

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getSortKey: function() {
		return this._value;
	},

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @inheritdoc
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.StringValue ) ) {
			return false;
		}

		return this.getValue() === value.getValue();
	},

	/**
	 * @inheritdoc
	 *
	 * @return {string}
	 */
	toJSON: function() {
		return this._value;
	}

} );

/**
 * @inheritdoc
 *
 * @return {dataValues.StringValue}
 */
SELF.newFromJSON = function( json ) {
	return new SELF( json );
};

/**
 * @inheritdoc
 * @property {string} [TYPE='string']
 * @static
 */
SELF.TYPE = 'string';

dv.registerDataValue( SELF );

}( dataValues, util ) );
