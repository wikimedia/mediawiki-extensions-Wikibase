/**
 * @file
 * @ingroup DataValues
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $ ) {
'use strict';

/**
 * Base constructor for objects representing a data value. DataValue objects are immutable, meaning
 * that the actual value can't be altered.
 *
 * @constructor
 * @abstract
 * @since 0.1
 */
dv.DataValue = function() {};
dv.DataValue.prototype = {

	/**
	 * Returns the most basic representation of this Object's value.
	 *
	 * @since 0.1
	 *
	 * @return {*}
	 */
	getValue: dv.util.abstractMember,

	/**
	 * Returns a key that can be used for sorting the data value.
	 * Can be either numeric or a string.
	 *
	 * NOTE: this could very well be set by the API, together with the value. Since the value is
	 *       immutable, this won't change as well and there is no need for having the logic here.
	 *
	 * @since 0.1
	 *
	 * @return String|Number
	 */
	getSortKey: dv.util.abstractMember,

	/**
	 * Returns a simple JSON structure representing this data value.
	 *
	 * @since 0.1
	 *
	 * @return Object
	 */
	toJSON: dv.util.abstractMember,

	/**
	 * Returns whether this value equals some other given value.
	 *
	 * @since 0.1
	 *
	 * @param dataValue DataValue
	 *
	 * @return Boolean
	 */
	equals: dv.util.abstractMember,

	/**
	 * Returns the type identifier for this data value.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	getType: function() {
		return this.constructor.TYPE;
	}
};

/**
 * Type of the DataValue. A static definition of the type like this has to be defined for all
 * DataValue implementations.
 * @type String
 */
dv.DataValue.TYPE = null;

}( dataValues, jQuery ) );
