( function( $ ) {
'use strict';

/**
 * Abstract Snak base class featuring a property id.
 * @class Snak
 * @abstract
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {string} propertyId
 * @param {string|null} [hash=null]
 *
 * @throws {Error} when trying to instantiate an abstract Snak object.
 * @throws {Error} when the property id is omitted.
 */
var SELF = function WbDataModelSnak( propertyId, hash ) {
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract Snak of no specific type' );
	} else if( !propertyId ) {
		throw new Error( 'Property ID is required for constructing new Snak' );
	}
	this._propertyId = propertyId;
	this._hash = hash || null;
};

/**
 * String to identify this type of Snak.
 * @property {string} [TYPE=null]
 * @static
 */
SELF.TYPE = null;

/**
 * @class Snak
 */
$.extend( SELF.prototype, {
	/**
	 * @property {string}
	 * @private
	 */
	_propertyId: null,

	/**
	 * @property {string|null}
	 * @private
	 */
	_hash: null,

	/**
	 * Returns the Snak type.
	 *
	 * @return {string}
	 */
	getType: function() {
		return this.constructor.TYPE;
	},

	/**
	 * Returns the ID of the Property featured by the Snak.
	 *
	 * @return {string}
	 */
	getPropertyId: function() {
		return this._propertyId;
	},

	/**
	 * Returns the hash of this Snak.
	 * Can be null if this Snak was constructed client-side,
	 * since hashes can only be computed server-side.
	 *
	 * @return {string|null}
	 */
	getHash: function() {
		return this._hash;
	},

	/**
	 * @param {*} snak
	 * @return {boolean}
	 */
	equals: function( snak ) {
		if( !( snak instanceof this.constructor ) ) {
			return false;
		}

		return snak === this
			|| this.getPropertyId() === snak.getPropertyId() && this.getType() === snak.getType();
	}
} );

module.exports = SELF;

}( jQuery ) );
