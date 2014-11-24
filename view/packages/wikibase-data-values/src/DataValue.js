( function( dv, $, util ) {
'use strict';

/**
 * Base constructor for objects representing a data value. DataValue objects are immutable, meaning
 * that the actual value can't be altered.
 * @class dataValues.DataValue
 * @abstract
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner
 *
 * @constructor
 */
var SELF = dv.DataValue = function DvDataValue() {
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract DataValue of no specific type' );
	}
};

/**
 * Type of the DataValue. A static definition of the type like this has to be defined for all
 * DataValue implementations.
 * @type String
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {

	/**
	 * Returns the most basic representation of this Object's value.
	 *
	 * @since 0.1
	 *
	 * @return {*}
	 */
	getValue: util.abstractMember,

	/**
	 * Returns a key that can be used for sorting the data value.
	 * Can be either numeric or a string.
	 *
	 * NOTE: this could very well be set by the API, together with the value. Since the value is
	 *       immutable, this won't change as well and there is no need for having the logic here.
	 *
	 * @since 0.1
	 *
	 * @return string|number
	 */
	getSortKey: util.abstractMember,

	/**
	 * Returns a simple JSON structure representing this data value.
	 *
	 * @since 0.1
	 *
	 * @return *
	 */
	toJSON: util.abstractMember,

	/**
	 * Returns whether this value equals some other given value.
	 *
	 * @since 0.1
	 *
	 * @param dataValue DataValue
	 *
	 * @return boolean
	 */
	equals: util.abstractMember,

	/**
	 * Returns the type identifier for this data value.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	getType: function() {
		return this.constructor.TYPE;
	}
} );

}( dataValues, jQuery, util ) );
