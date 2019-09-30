( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization,
	datamodel = require( 'wikibase.datamodel' ),
	FingerprintDeserializer = require( './Deserializers/FingerprintDeserializer.js' ),
	MultiTermDeserializer = require( './Deserializers/MultiTermDeserializer.js' ),
	MultiTermMapDeserializer = require( './Deserializers/MultiTermMapDeserializer.js' ),
	SiteLinkDeserializer = require( './Deserializers/SiteLinkDeserializer.js' ),
	SiteLinkSetDeserializer = require( './Deserializers/SiteLinkSetDeserializer.js' ),
	ReferenceListDeserializer = require( './Deserializers/ReferenceListDeserializer.js' ),
	ReferenceDeserializer = require( './Deserializers/ReferenceDeserializer.js' ),
	StatementGroupDeserializer = require( './Deserializers/StatementGroupDeserializer.js' ),
	ClaimDeserializer = require( './Deserializers/ClaimDeserializer.js' ),
	SnakListDeserializer = require( './Deserializers/SnakListDeserializer.js' );

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

	this.registerDeserializer( ClaimDeserializer, datamodel.Claim );
	this.registerDeserializer( MODULE.EntityDeserializer, datamodel.Entity );
	this.registerDeserializer( FingerprintDeserializer, datamodel.Fingerprint );
	this.registerDeserializer( MultiTermDeserializer, datamodel.MultiTerm );
	this.registerDeserializer( MultiTermMapDeserializer, datamodel.MultiTermMap );
	this.registerDeserializer( ReferenceDeserializer, datamodel.Reference );
	this.registerDeserializer( ReferenceListDeserializer, datamodel.ReferenceList );
	this.registerDeserializer( SiteLinkDeserializer, datamodel.SiteLink );
	this.registerDeserializer( SiteLinkSetDeserializer, datamodel.SiteLinkSet );
	this.registerDeserializer( MODULE.SnakDeserializer, datamodel.Snak );
	this.registerDeserializer( SnakListDeserializer, datamodel.SnakList );
	this.registerDeserializer( MODULE.StatementDeserializer, datamodel.Statement );
	this.registerDeserializer( StatementGroupDeserializer, datamodel.StatementGroup );
	this.registerDeserializer( MODULE.StatementGroupSetDeserializer, datamodel.StatementGroupSet );
	this.registerDeserializer( MODULE.StatementListDeserializer, datamodel.StatementList );
	this.registerDeserializer( MODULE.TermDeserializer, datamodel.Term );
	this.registerDeserializer( MODULE.TermMapDeserializer, datamodel.TermMap );
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
