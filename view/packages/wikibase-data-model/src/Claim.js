( function( $ ) {
'use strict';

var Snak = require( './Snak.js' ),
	SnakList = require( './SnakList.js' );

/**
 * Object featuring a main snak and a list of qualifiers.
 * @class Claim
 * @since 0.3
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @constructor
 *
 * @param {Snak} mainSnak
 * @param {SnakList|null} [qualifiers=new SnakList()]
 * @param {string|null} [guid=null] The Global Unique Identifier of this Claim. Can be null if this
 *        is a new Claim, not yet stored in the database and associated with some entity.
 */
var SELF = function WbDataModelClaim( mainSnak, qualifiers, guid ) {
	this.setMainSnak( mainSnak );
	this.setQualifiers( qualifiers || new SnakList() );
	this._guid = guid || null;
};

/**
 * @class Claim
 */
$.extend( SELF.prototype, {
	/**
	 * @property {Snak}
	 * @private
	 */
	_mainSnak: null,

	/**
	 * @property {SnakList}
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
	 * @return {Snak}
	 */
	getMainSnak: function() {
		return this._mainSnak;
	},

	/**
	 * Overwrites the current main Snak.
	 *
	 * @param {Snak} mainSnak
	 *
	 * @throws {Error} if parameter is not a Snak instance.
	 */
	setMainSnak: function( mainSnak ) {
		if( !( mainSnak instanceof Snak ) ) {
			throw new Error( 'Main snak needs to be a Snak instance' );
		}
		this._mainSnak = mainSnak;
	},

	/**
	 * @return {SnakList}
	 */
	getQualifiers: function() {
		return this._qualifiers;
	},

	/**
	 * @param {SnakList} qualifiers
	 *
	 * @throws {Error} if parameter is not a SnakList instance.
	 */
	setQualifiers: function( qualifiers ) {
		if( !( qualifiers instanceof SnakList ) ) {
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

module.exports = SELF;

}( jQuery ) );
