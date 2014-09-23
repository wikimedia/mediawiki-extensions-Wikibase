/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
'use strict';

/**
 * Represents a Wikibase Claim.
 * @constructor
 * @since 0.3
 *
 * @param {wb.datamodel.Snak} mainSnak
 * @param {wikibase.datamodel.SnakList|null} [qualifiers]
 * @param {String|null} [guid] The Global Unique Identifier of this Claim. Can be omitted or null
 *        if this is a new Claim, not yet stored in the database and associated with some entity.
 */
var SELF = wb.datamodel.Claim = function WbClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new wb.datamodel.SnakList() );
	this._guid = guid || null;
};

/**
 * String to identify if the object is a statement or a claim.
 * @type {string}
 */
SELF.TYPE = 'claim';

$.extend( SELF.prototype, {
	/**
	 * @type wb.datamodel.Snak
	 */
	_mainSnak: null,

	/**
	 * @type {wb.datamodel.SnakList}
	 */
	_qualifiers: null,

	/**
	 * @type String|null
	 */
	_guid: null,

	/**
	 * Returns the GUID (Global Unique Identifier) of the Claim. Returns null if the claim is not
	 * yet stored in the database.
	 *
	 * @return String|null
	 */
	getGuid: function() {
		return this._guid;
	},

	/**
	 * Returns the main Snak.
	 *
	 * @return {wb.datamodel.Snak}
	 */
	getMainSnak: function() {
		return this._mainSnak;
	},

	/**
	 * Overwrites the current main Snak.
	 *
	 * @param {wb.datamodel.Snak} mainSnak
	 */
	setMainSnak: function( mainSnak ) {
		if( !( mainSnak instanceof wb.datamodel.Snak ) ) {
			throw new Error( 'For creating a new claim, at least a Main Snak is required' );
		}
		this._mainSnak = mainSnak;
	},

	/**
	 * @return {wikibase.datamodel.SnakList}
	 */
	getQualifiers: function( propertyId ) {
		if( !propertyId ) {
			return this._qualifiers;
		}

		var filteredQualifiers = new wb.datamodel.SnakList();

		this._qualifiers.each( function( i, snak ) {
			if( snak.getPropertyId() === propertyId ) {
				filteredQualifiers.addSnak( snak );
			}
		} );

		return filteredQualifiers;
	},

	/**
	 * @param {wikibase.datamodel.SnakList} qualifiers
	 */
	setQualifiers: function( qualifiers ) {
		if( !( qualifiers instanceof wb.datamodel.SnakList ) ) {
			throw new Error( 'Qualifiers have to be a wb.datamodel.SnakList object' );
		}
		this._qualifiers = qualifiers;
	},

	/**
	 * Returns whether this Claim is equal to another Claim. Two Claims are considered equal
	 * if they are of the same type and have the same value. The value does not include the guid,
	 * so Claims with the same value but different guids are still considered equal.
	 *
	 * @param {wb.datamodel.Claim|*} other If this is not a wb.datamodel.Claim, false will be returned.
	 * @return boolean
	 */
	equals: function( other ) {
		return this === other
			|| ( // snaks have no IDs, so we don't have to worry about comparing any
				other instanceof this.constructor
				&& this._mainSnak.equals( other.getMainSnak() )
				&& this._qualifiers.equals( other.getQualifiers() )
			);
	}
} );

}( wikibase, jQuery ) );
