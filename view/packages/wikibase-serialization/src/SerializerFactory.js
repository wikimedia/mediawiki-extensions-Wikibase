( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

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

	this.registerSerializer( MODULE.ClaimSerializer, wb.datamodel.Claim );
	this.registerSerializer( MODULE.EntitySerializer, wb.datamodel.Entity );
	this.registerSerializer( MODULE.FingerprintSerializer, wb.datamodel.Fingerprint );
	this.registerSerializer( MODULE.MultiTermMapSerializer, wb.datamodel.MultiTermMap );
	this.registerSerializer( MODULE.MultiTermSerializer, wb.datamodel.MultiTerm );
	this.registerSerializer( MODULE.ReferenceListSerializer, wb.datamodel.ReferenceList );
	this.registerSerializer( MODULE.ReferenceSerializer, wb.datamodel.Reference );
	this.registerSerializer( MODULE.SiteLinkSerializer, wb.datamodel.SiteLink );
	this.registerSerializer( MODULE.SiteLinkSetSerializer, wb.datamodel.SiteLinkSet );
	this.registerSerializer( MODULE.SnakListSerializer, wb.datamodel.SnakList );
	this.registerSerializer( MODULE.SnakSerializer, wb.datamodel.Snak );
	this.registerSerializer( MODULE.StatementGroupSerializer, wb.datamodel.StatementGroup );
	this.registerSerializer( MODULE.StatementGroupSetSerializer, wb.datamodel.StatementGroupSet );
	this.registerSerializer( MODULE.StatementListSerializer, wb.datamodel.StatementList );
	this.registerSerializer( MODULE.StatementSerializer, wb.datamodel.Statement );
	this.registerSerializer( MODULE.TermSerializer, wb.datamodel.Term );
	this.registerSerializer( MODULE.TermMapSerializer, wb.datamodel.TermMap );
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
