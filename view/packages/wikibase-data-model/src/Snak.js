/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, dv, $ ) {
'use strict';

/**
 * Represents a Wikibase Snak.
 * @constructor
 * @abstract
 * @since 0.3
 *
 * @param {String} propertyId
 */
var SELF = wb.datamodel.Snak = function WbSnak( propertyId ) {
	// check whether the Snak has a type, doesn't make sense to create an instance of wb.datamodel.Snak!
	if( !this.constructor.TYPE ) {
		throw new Error( 'Can not create abstract Snak of no specific type' );
	}
	if( !propertyId ) {
		throw new Error( 'Property ID is required for constructing new Snak' );
	}
	this._propertyId = propertyId;
};

/**
 * String to identify this type of Snak
 * @type String
 */
SELF.TYPE = null;

$.extend( SELF.prototype, {
	/**
	 * @type Number
	 */
	_propertyId: null,

	/**
	 * Returns what type of Snak this is.
	 *
	 * @return String
	 */
	getType: function() {
		return this.constructor.TYPE;
	},

	/**
	 * Returns the ID of the property entity the Snak relates to.
	 *
	 * @return string
	 */
	getPropertyId: function() {
		return this._propertyId;
	},

	/**
	 * Returns whether this Snak is equal to another Snak. This means that their property and type
	 * are the same, as well as any other attributes they might have depending on their Snak type.
	 *
	 * @param {wb.datamodel.Snak|*} that
	 * @return {Boolean}
	 */
	equals: function( that ) {
		if( !( that instanceof this.constructor ) ) {
			return false;
		}

		return that === this
			|| (
				this.getPropertyId() === that.getPropertyId()
				&& this.getType() === that.getType()
			);
	}
} );

}( wikibase, dataValues, jQuery ) );
