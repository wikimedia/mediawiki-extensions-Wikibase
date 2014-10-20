/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * Factory for creating deserializers specific to certain objects, e.g. of the Wikibase data model.
 * @constructor
 * @since 2.0
 */
var SELF = MODULE.DeserializerFactory = function wbDeserializerFactory() {
	this._strategyProvider = new MODULE.StrategyProvider();

	this.registerDeserializer( MODULE.ClaimDeserializer, wb.datamodel.Claim );
	this.registerDeserializer( MODULE.ClaimGroupDeserializer, wb.datamodel.ClaimGroup );
	this.registerDeserializer( MODULE.ClaimGroupSetDeserializer, wb.datamodel.ClaimGroupSet );
	this.registerDeserializer( MODULE.ClaimListDeserializer, wb.datamodel.ClaimList );
	this.registerDeserializer( MODULE.EntityDeserializer, wb.datamodel.Entity );
	this.registerDeserializer( MODULE.EntityIdDeserializer, wb.datamodel.EntityId );
	this.registerDeserializer( MODULE.FingerprintDeserializer, wb.datamodel.Fingerprint );
	this.registerDeserializer( MODULE.MultiTermDeserializer, wb.datamodel.MultiTerm );
	this.registerDeserializer( MODULE.MultiTermMapDeserializer, wb.datamodel.MultiTermMap );
	this.registerDeserializer( MODULE.ReferenceDeserializer, wb.datamodel.Reference );
	this.registerDeserializer( MODULE.ReferenceListDeserializer, wb.datamodel.ReferenceList );
	this.registerDeserializer( MODULE.SiteLinkDeserializer, wb.datamodel.SiteLink );
	this.registerDeserializer( MODULE.SiteLinkSetDeserializer, wb.datamodel.SiteLinkSet );
	this.registerDeserializer( MODULE.SnakDeserializer, wb.datamodel.Snak );
	this.registerDeserializer( MODULE.SnakListDeserializer, wb.datamodel.SnakList );
	this.registerDeserializer( MODULE.StatementDeserializer, wb.datamodel.Statement );
	this.registerDeserializer( MODULE.StatementGroupDeserializer, wb.datamodel.StatementGroup );
	this.registerDeserializer( MODULE.StatementGroupSetDeserializer, wb.datamodel.StatementGroupSet );
	this.registerDeserializer( MODULE.StatementListDeserializer, wb.datamodel.StatementList );
	this.registerDeserializer( MODULE.TermDeserializer, wb.datamodel.Term );
	this.registerDeserializer( MODULE.TermMapDeserializer, wb.datamodel.TermMap );
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.serialization.StrategyProvider}
	 */
	_strategyProvider: null,

	/**
	 * @param {Function} Constructor
	 * @return {wikibase.serialization.Deserializer}
	 */
	newDeserializerFor: function( Constructor ) {
		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No proper constructor provided for choosing a Deserializer' );
		}

		return new ( this._strategyProvider.getStrategyFor( Constructor ) )();
	},

	/**
	 * @param {Function} Deserializer
	 * @param {Function} Constructor
	 */
	registerDeserializer: function( Deserializer, Constructor ) {
		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No constructor (function) provided' );
		} else if( !( ( new Deserializer() ) instanceof MODULE.Deserializer ) ) {
			throw new Error( 'Given Deserializer is not an implementation of '
				+ 'wb.serialization.Deserializer' );
		}
		this._strategyProvider.registerStrategy( Deserializer, Constructor );
	}
} );

}( wikibase, jQuery ) );
