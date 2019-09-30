( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization,
	datamodel = require( 'wikibase.datamodel' ),
	TermSerializer = require( './Serializers/TermSerializer.js' );

/**
 * Factory for creating serializers specific to certain objects, e.g. of the Wikibase data model.
 * @class wikibase.serialization.SerializerFactory
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
var SELF = MODULE.SerializerFactory = function WbSerializationSerializerFactory() {
	this._strategyProvider = new MODULE.StrategyProvider();

	this.registerSerializer( MODULE.ClaimSerializer, datamodel.Claim );
	this.registerSerializer( MODULE.EntitySerializer, datamodel.Entity );
	this.registerSerializer( MODULE.FingerprintSerializer, datamodel.Fingerprint );
	this.registerSerializer( MODULE.MultiTermMapSerializer, datamodel.MultiTermMap );
	this.registerSerializer( MODULE.MultiTermSerializer, datamodel.MultiTerm );
	this.registerSerializer( MODULE.ReferenceListSerializer, datamodel.ReferenceList );
	this.registerSerializer( MODULE.ReferenceSerializer, datamodel.Reference );
	this.registerSerializer( MODULE.SiteLinkSerializer, datamodel.SiteLink );
	this.registerSerializer( MODULE.SiteLinkSetSerializer, datamodel.SiteLinkSet );
	this.registerSerializer( MODULE.SnakListSerializer, datamodel.SnakList );
	this.registerSerializer( MODULE.SnakSerializer, datamodel.Snak );
	this.registerSerializer( MODULE.StatementGroupSerializer, datamodel.StatementGroup );
	this.registerSerializer( MODULE.StatementGroupSetSerializer, datamodel.StatementGroupSet );
	this.registerSerializer( MODULE.StatementListSerializer, datamodel.StatementList );
	this.registerSerializer( MODULE.StatementSerializer, datamodel.Statement );
	this.registerSerializer( TermSerializer, datamodel.Term );
	this.registerSerializer( MODULE.TermMapSerializer, datamodel.TermMap );
};

$.extend( SELF.prototype, {
	/**
	 * @property {wikibase.serialization.StrategyProvider}
	 * @private
	 */
	_strategyProvider: null,

	/**
	 * @param {Object|Function} objectOrConstructor
	 * @return {wikibase.serialization.Serializer}
	 *
	 * @throws {Error} if argument is omitted.
	 * @throws {Error} if argument is not a constructor or the object constructor could not be
	 *         determined.
	 */
	newSerializerFor: function( objectOrConstructor ) {
		if( !objectOrConstructor ) {
			throw new Error( 'Constructor or object expected' );
		}

		var Constructor = $.isFunction( objectOrConstructor )
			? objectOrConstructor
			: objectOrConstructor.constructor;

		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No proper constructor provided for choosing a Serializer' );
		}

		return new ( this._strategyProvider.getStrategyFor( Constructor ) )();
	},

	/**
	 * @param {Function} Serializer
	 * @param {Function} Constructor
	 *
	 * @throws {Error} if serializer constructor does not inherit from Serializer base class.
	 * @throws {Error} if constructor is not a function.
	 */
	registerSerializer: function( Serializer, Constructor ) {
		if( !$.isFunction( Constructor ) ) {
			throw new Error( 'No constructor (function) provided' );
		} else if( !( ( new Serializer() ) instanceof MODULE.Serializer ) ) {
			throw new Error( 'Given Serializer is not an implementation of '
				+ 'wb.serialization.Serializer' );
		}
		this._strategyProvider.registerStrategy( Serializer, Constructor );
	}
} );

}( wikibase, jQuery ) );
