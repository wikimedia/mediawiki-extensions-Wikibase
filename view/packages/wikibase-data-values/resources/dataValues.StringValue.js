/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, undefined ) {
'use strict';

var PARENT = dv.DataValue,
	constructor = function( value ) {};

/**
 * Constructor for creating a data value representing a string.
 *
 * @constructor
 * @extends dv.Value
 * @since 0.2
 *
 * @param {String} value
 */
dv.StringValue = dv.util.inherit( PARENT, constructor, {

	/**
	 * @see dv.Value.getType
	 */
	getType: function() {
		return 'string';
	}
} );

}( dataValues, jQuery ) );
