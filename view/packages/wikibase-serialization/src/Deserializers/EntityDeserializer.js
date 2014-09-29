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
	this._strategyProvider = new MODULE.StrategyProvider();
	this._strategyProvider.registerStrategy(
		new MODULE.ItemDeserializer(), wb.datamodel.Item.TYPE
	);
	this._strategyProvider.registerStrategy(
		new MODULE.PropertyDeserializer(), wb.datamodel.Property.TYPE
	);
}, {
	/**
	 * @type {wikibase.serialization.StrategyProvider}
	 */
	_strategyProvider: null,

		/**
	 * @param {wikibase.serialization.Deserializer} deserializer
	 * @param {string} entityType
	 */
	registerStrategy: function( deserializer, entityType ) {
		this._strategyProvider.registerStrategy( deserializer, entityType );
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

		return this._strategyProvider
			.getStrategyFor( serialization.type )
			.deserialize( serialization );
	}
} );

}( wikibase, util ) );
