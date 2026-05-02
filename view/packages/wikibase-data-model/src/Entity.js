( function( $ ) {
	'use strict';

/**
 * Abstract Entity base class featuring an id and a fingerprint.
 * @class Entity
 * @abstract
 * @since 0.3
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @throws {Error} when trying to instantiate since Entity is abstract.
 */
var SELF = function WbDataModelEntity() {
	throw new Error( 'Cannot construct abstract Entity object' );
};

/**
 * String to identify this type of Entity.
 * @property {string} [TYPE=null]
 * @static
 */
SELF.TYPE = null;

/**
 * @class Entity
 */
$.extend( SELF.prototype, {
	/**
	 * @property {string}
	 * @private
	 */
	_id: null,

	/**
	 * @return {string}
	 */
	getId: function() {
		return this._id;
	},

	/**
	 * Returns what type of Entity this is.
	 *
	 * @return string
	 */
	getType: function() {
		return this.constructor.TYPE;
	},

	/**
	 * @abstract
	 *
	 * @return {boolean}
	 */
	isEmpty: util.abstractMember,

	/**
	 * @abstract
	 *
	 * @param {*} entity
	 * @return {boolean}
	 */
	equals: util.abstractMember
} );

module.exports = SELF;

}( jQuery ) );
