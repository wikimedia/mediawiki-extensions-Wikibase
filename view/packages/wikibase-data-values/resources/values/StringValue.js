/**
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 *
 * @author Daniel Werner
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( value ) {
		this._value = value;
	};

/**
 * Constructor for creating a data value representing a string.
 *
 * @constructor
 * @extends dv.DataValue
 * @since 0.1
 *
 * @param {String} value
 */
dv.StringValue = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.DataValue.getType
	 *
	 * @since 0.1
	 */
	getType: function() {
		return 'string';
	},

	/**
	 * @see dv.DataValue.getSortKey
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getSortKey: function() {
		return this._value;
	},

	/**
	 * @see dv.DataValue.getValue
	 *
	 * @since 0.1
	 *
	 * @return String
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
	 */
	toJSON: function() {
		return this._value;
	}

} );

dv.StringValue.newFromJSON = function( json ) {
	return new dv.StringValue( json );
};

}( dataValues, jQuery ) );
