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
		this.value = value;
	};

/**
 * Constructor for creating a data value representing a string.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.1
 *
 * @param {String} value
 */
dv.StringValue = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.Value.getType
	 */
	getType: function() {
		return 'string';
	},

	/**
	 * @see dv.Value.getSortKey
	 */
	getSortKey: function() {
		return this.value;
	},

	/**
	 * @see dv.Value.getValue
	 */
	getValue: function() {
		return this.value;
	},

	/**
	 * @see dv.Value.equals
	 */
	equals: function( value ) {
		return this.value == value;
	},

	/**
	 * @see dv.Value.toJSON
	 */
	toJSON: function( value ) {
		return this.value;
	}

} );

dv.StringValue.newFromJSON = function( json ) {
	return new dv.StringValue( json );
};

}( dataValues, jQuery ) );
