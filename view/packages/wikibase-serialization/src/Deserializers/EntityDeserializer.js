/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @constructor
 * @extends {wikibase.serialization.Deserializer}
 * @since 1.0
 */
MODULE.EntityDeserializer = util.inherit( 'WbEntityDeserializer', PARENT, function() {
	this._strategies = [];
	this.registerStrategy( new MODULE.ItemDeserializer(), wb.datamodel.Item.TYPE );
	this.registerStrategy( new MODULE.PropertyDeserializer(), wb.datamodel.Property.TYPE );
}, {
	/**
	 * @type {Object[]}
	 */
	_strategies: null,

	/**
	 * @param {wikibase.serialization.Deserializer} deserializer
	 * @param {string} entityType
	 */
	registerStrategy: function( deserializer, entityType ) {
		if( this._hasStrategyFor( entityType ) ) {
			throw new Error( 'Deserializer for entity type "' + entityType
				+ '" is registered already' );
		}

		this._strategies.push( {
			entityType: entityType,
			deserializer: deserializer
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
	 * @return {wikibase.serialization.Deserializer}
	 */
	_getStrategyFor: function( entityType ) {
		for( var i = 0; i < this._strategies.length; i++ ) {
			if( entityType === this._strategies[i].entityType ) {
				return this._strategies[i].deserializer;
			}
		}

		throw new Error( 'Deserializing entity type "' + entityType + '" is not supported' );
	},

	/**
	 * @see wikibase.serialization.Deserializer.deserialize
	 *
	 * @return {wikibase.datamodel.Entity}
	 */
	deserialize: function( serialization ) {
		if( !serialization.type || typeof serialization.type !== 'string' ) {
			throw new Error( 'Can not determine type of Entity from serialized object' );
		}

		return this._getStrategyFor( serialization.type ).deserialize( serialization );
	}
} );

}( wikibase, util ) );
