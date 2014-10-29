/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
'use strict';

/**
 * @constructor
 * @since 0.3
 *
 * @param {wikibase.datamodel.Snak} mainSnak
 * @param {wikibase.datamodel.SnakList|null} [qualifiers]
 * @param {string|null} [guid] The Global Unique Identifier of this Claim. Can be omitted or null
 *        if this is a new Claim, not yet stored in the database and associated with some entity.
 */
var SELF = wb.datamodel.Claim = function WbDataModelClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new wb.datamodel.SnakList() );
	this._guid = guid || null;
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.datamodel.Snak}
	 */
	_mainSnak: null,

	/**
	 * @type {wikibase.datamodel.SnakList}
	 */
	_qualifiers: null,

	/**
	 * @type {string|null}
	 */
	_guid: null,

	/**
	 * Returns the GUID (Global Unique Identifier) of the Claim. Returns null if the claim is not
	 * yet stored in the database.
	 *
	 * @return {string|null}
	 */
	getGuid: function() {
		return this._guid;
	},

	/**
	 * Returns the main Snak.
	 *
	 * @return {wikibase.datamodel.Snak}
	 */
	getMainSnak: function() {
		return this._mainSnak;
	},

	/**
	 * Overwrites the current main Snak.
	 *
	 * @param {wikibase.datamodel.Snak} mainSnak
	 */
	setMainSnak: function( mainSnak ) {
		if( !( mainSnak instanceof wb.datamodel.Snak ) ) {
			throw new Error( 'Main snak needs to be a Snak instance' );
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
				filteredQualifiers.addItem( snak );
			}
		} );

		return filteredQualifiers;
	},

	/**
	 * @param {wikibase.datamodel.SnakList} qualifiers
	 */
	setQualifiers: function( qualifiers ) {
		if( !( qualifiers instanceof wb.datamodel.SnakList ) ) {
			throw new Error( 'Qualifiers have to be a SnakList object' );
		}
		this._qualifiers = qualifiers;
	},

	/**
	 * @param {*} claim
	 * @return {boolean}
	 */
	equals: function( claim ) {
		return claim === this
			|| claim instanceof this.constructor
				&& this._mainSnak.equals( claim.getMainSnak() )
				&& this._qualifiers.equals( claim.getQualifiers() );
	}
} );

}( wikibase, jQuery ) );
