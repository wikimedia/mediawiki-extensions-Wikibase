( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * Factory for creating deserializers specific to certain objects, e.g. of the Wikibase data model.
 * @class wikibase.serialization.DeserializerFactory
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
var SELF = MODULE.DeserializerFactory = function WbSerializationDeserializerFactory() {
	this._strategyProvider = new MODULE.StrategyProvider();

	this.registerDeserializer( MODULE.ClaimDeserializer, wb.datamodel.Claim );
	this.registerDeserializer( MODULE.EntityDeserializer, wb.datamodel.Entity );
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
	 * @property {wikibase.serialization.StrategyProvider}
	 * @private
	 */
	_strategyProvider: null,

	/**
	 * @param {Function} Constructor
	 * @return {wikibase.serialization.Deserializer}
	 *
	 * @throws {Error} if constructor is not a function.
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
	 *
	 * @throws {Error} if deserializer constructor does not inherit from Deserializer base class.
	 * @throws {Error} if constructor is not a function.
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
