/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends {wikibase.serialization.Serializer}
 * @since 2.0
 */
MODULE.EntitySerializer = util.inherit( 'WbEntitySerializer', PARENT, function() {
	this._strategies = [];
	this.registerStrategy( new MODULE.ItemSerializer(), wb.datamodel.Item.TYPE );
	this.registerStrategy( new MODULE.PropertySerializer(), wb.datamodel.Property.TYPE );
}, {
	/**
	 * @type {Object[]}
	 */
	_strategies: null,

	/**
	 * @param {wikibase.serialization.Serializer} serializer
	 * @param {string} entityType
	 */
	registerStrategy: function( serializer, entityType ) {
		if( this._hasStrategyFor( entityType ) ) {
			throw new Error( 'Serializer for entity type "' + entityType
				+ '" is registered already' );
		}

		this._strategies.push( {
			entityType: entityType,
			serializer: serializer
		} );
	},

	/**
	 * @param {string} entityType
	 * @return {boolean}
	 */
	_hasStrategyFor: function( entityType ) {
		for( var i = 0; i < this._strategies.length; i++ ) {
			if( entityType === this._strategies[i].entityType ) {
				return true;
			}
		}
		return false;
	},

	/**
	 * @param {string} entityType
	 * @return {wikibase.serialization.Serializer}
	 */
	_getStrategy: function( entityType ) {
		for( var i = 0; i < this._strategies.length; i++ ) {
			if( entityType === this._strategies[i].entityType ) {
				return this._strategies[i].serializer;
			}
		}

		throw new Error( 'Serializing entity type "' + entityType + '" is not supported' );
	},

	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @return {Object}
	 */
	serialize: function( entity ) {
		if( !( entity instanceof wb.datamodel.Entity ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Entity' );
		}

		return this._getStrategy( entity.getType() ).serialize( entity );
	}
} );

}( wikibase, util ) );
