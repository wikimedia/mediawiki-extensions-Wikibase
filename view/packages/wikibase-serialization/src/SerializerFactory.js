/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $ ) {
	'use strict';

var MODULE = wb.serialization;

/**
 * Factory for creating serializers specific to certain objects, e.g. of the Wikibase data model.
 * @constructor
 * @since 1.0
 */
var SELF = MODULE.SerializerFactory = function WbSerializerProvider() {
	this._strategyProvider = new MODULE.StrategyProvider();

	this.registerSerializer( MODULE.ClaimGroupSerializer, wb.datamodel.ClaimGroup );
	this.registerSerializer( MODULE.ClaimGroupSetSerializer, wb.datamodel.ClaimGroupSet );
	this.registerSerializer( MODULE.ClaimListSerializer, wb.datamodel.ClaimList );
	this.registerSerializer( MODULE.ClaimSerializer, wb.datamodel.Claim );
	this.registerSerializer( MODULE.EntityIdSerializer, wb.datamodel.EntityId );
	this.registerSerializer( MODULE.EntitySerializer, wb.datamodel.Entity );
	this.registerSerializer( MODULE.FingerprintSerializer, wb.datamodel.Fingerprint );
	this.registerSerializer( MODULE.MultiTermSerializer, wb.datamodel.MultiTerm );
	this.registerSerializer( MODULE.MultiTermSetSerializer, wb.datamodel.MultiTermSet );
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
	this.registerSerializer( MODULE.TermSetSerializer, wb.datamodel.TermSet );
};

$.extend( SELF.prototype, {
	/**
	 * @type {wikibase.serialization.StrategyProvider}
	 */
	_strategyProvider: null,

	/**
	 * @param {Object|Function} objectOrConstructor
	 * @return {wikibase.serialization.Serializer}
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
