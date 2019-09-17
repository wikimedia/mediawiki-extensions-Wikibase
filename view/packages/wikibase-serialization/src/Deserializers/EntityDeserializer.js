( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer,
	ItemDeserializer = require( './ItemDeserializer.js' ),
	PropertyDeserializer = require( './PropertyDeserializer.js' ),
	StrategyProvider = require( '../StrategyProvider.js' );

/**
 * @class wikibase.serialization.EntityDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 1.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.EntityDeserializer = util.inherit( 'WbEntityDeserializer', PARENT, function() {
	this._strategyProvider = new StrategyProvider();
	this._strategyProvider.registerStrategy(
		new ItemDeserializer(), wb.datamodel.Item.TYPE
	);
	this._strategyProvider.registerStrategy(
		new PropertyDeserializer(), wb.datamodel.Property.TYPE
	);
}, {
	/**
	 * @property {wikibase.serialization.StrategyProvider}
	 * @private
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
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Entity}
	 *
	 * @throws {Error} if unable to detect the entity type from the serialization.
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
