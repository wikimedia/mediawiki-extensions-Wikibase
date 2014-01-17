/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, util ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( value ) {
		if( typeof value !== 'string' ) {
			throw new Error( 'A string value has to be given' );
		}
		this._value = value;
	};

/**
 * Constructor for creating a data value representing a string.
 *
 * @constructor
 * @extends dv.DataValue
 * @since 0.1
 *
 * @param {string} value
 */
dv.StringValue = util.inherit( 'DvStringValue', PARENT, constructor, {

	/**
	 * @see dv.DataValue.getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getSortKey: function() {
		return this._value;
	},

	/**
	 * @see dv.DataValue.getValue
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getValue: function() {
		return this._value;
	},

	/**
	 * @see dv.DataValue.equals
	 *
	 * @since 0.1
	 */
	equals: function( value ) {
		if ( !( value instanceof dv.StringValue ) ) {
			return false;
		}

		return this.getValue() === value.getValue();
	},

	/**
	 * @see dv.DataValue.toJSON
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	toJSON: function() {
		return this._value;
	}

} );

/**
 * @see dv.DataValue.newFromJSON
 */
dv.StringValue.newFromJSON = function( json ) {
	return new dv.StringValue( json );
};

/**
 * @see dv.DataValue.TYPE
 */
dv.StringValue.TYPE = 'string';

dv.registerDataValue( dv.StringValue );

}( dataValues, util ) );
