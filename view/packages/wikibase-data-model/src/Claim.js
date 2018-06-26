( function( wb, $ ) {
'use strict';

/**
 * Object featuring a main snak and a list of qualifiers.
 * @class wikibase.datamodel.Claim
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {wikibase.datamodel.Snak} mainSnak
 * @param {wikibase.datamodel.SnakList|null} [qualifiers=new wikibase.datamodel.SnakList()]
 * @param {string|null} [guid=null] The Global Unique Identifier of this Claim. Can be null if this
 *        is a new Claim, not yet stored in the database and associated with some entity.
 */
var SELF = wb.datamodel.Claim = function WbDataModelClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new wb.datamodel.SnakList() );
	this._guid = guid || null;
};

/**
 * @class wikibase.datamodel.Claim
 */
$.extend( SELF.prototype, {
	/**
	 * @property {wikibase.datamodel.Snak}
	 * @private
	 */
	_mainSnak: null,

	/**
	 * @property {wikibase.datamodel.SnakList}
	 * @private
	 */
	_qualifiers: null,

	/**
	 * @property {string|null}
	 * @private
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
	 *
	 * @throws {Error} if parameter is not a Snak instance.
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
	getQualifiers: function() {
		return this._qualifiers;
	},

	/**
	 * @param {wikibase.datamodel.SnakList} qualifiers
	 *
	 * @throws {Error} if parameter is not a SnakList instance.
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
