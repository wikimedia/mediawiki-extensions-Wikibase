/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
'use strict';

/**
 * @constructor
 * @abstract
 * @since 0.3
 *
 * @param {string} propertyId
 */
var SELF = wb.datamodel.Snak = function WbDataModelSnak( propertyId ) {
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract Snak of no specific type' );
	} else if( !propertyId ) {
		throw new Error( 'Property ID is required for constructing new Snak' );
	}
	this._propertyId = propertyId;
};

/**
 * String to identify this type of Snak.
 * @type {string}
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {
	/**
	 * @type {string}
	 */
	_propertyId: null,

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

}( wikibase, jQuery ) );
