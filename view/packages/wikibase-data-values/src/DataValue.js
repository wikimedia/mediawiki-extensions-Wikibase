( function( dv, $, util ) {
'use strict';

/**
 * Base constructor for objects representing a data value. DataValue objects are immutable, meaning
 * that the actual value can't be altered.
 * @class dataValues.DataValue
 * @abstract
 * @since 0.1
 * @license GPL-2.0+
 * @author Daniel Werner
 *
 * @constructor
 *
 * @throws {Error} if no static TYPE is defined on the DataValue constructor.
 */
var SELF = dv.DataValue = function DvDataValue() {
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract DataValue of no specific type' );
	}
};

/**
 * Type of the DataValue. A static definition of the type like this has to be defined for all
 * DataValue implementations.
 * @property {string} [TYPE='null']
 * @static
 */
SELF.TYPE = null;

/**
 * @class dataValues.DataValue
 */
$.extend( SELF.prototype, {

	/**
	 * Returns the most basic representation of this Object's value.
	 * @abstract
	 *
	 * @return {*}
	 */
	getValue: util.abstractMember,

	/**
	 * Returns a key that can be used for sorting the data value.
	 * Can be either numeric or a string.
	 * NOTE: this could very well be set by the API, together with the value. Since the value is
	 *       immutable, this won't change as well and there is no need for having the logic here.
	 * @abstract
	 *
	 * @return {string|number}
	 */
	getSortKey: util.abstractMember,

	/**
	 * Returns a simple JSON structure representing this data value.
	 * @abstract
	 *
	 * @return {*}
	 */
	toJSON: util.abstractMember,

	/**
	 * Returns whether this value equals some other given value.
	 * @abstract
	 *
	 * @param {*} dataValue
	 * @return {boolean}
	 */
	equals: util.abstractMember,

	/**
	 * Returns the type identifier for this data value.
	 *
	 * @return {string}
	 */
	getType: function() {
		return this.constructor.TYPE;
	}
} );

/**
 * Instantiates a DataValue object from provided JSON.
 * @static
 *
 * @param {*} json
 * @return {dataValues.DataValue}
 */
SELF.newFromJSON = util.abstractMember;

}( dataValues, jQuery, util ) );
