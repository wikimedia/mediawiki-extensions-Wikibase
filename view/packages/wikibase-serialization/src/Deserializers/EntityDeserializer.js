( function() {
	'use strict';

	var PARENT = require( './Deserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		ItemDeserializer = require( './ItemDeserializer.js' ),
		PropertyDeserializer = require( './PropertyDeserializer.js' ),
		StrategyProvider = require( '../StrategyProvider.js' );

	/**
	 * @class EntityDeserializer
	 * @extends Deserializer
	 * @since 1.0
	 * @license GPL-2.0+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 */
	module.exports = util.inherit( 'WbEntityDeserializer', PARENT, function() {
		this._strategyProvider = new StrategyProvider();
		this._strategyProvider.registerStrategy(
			new ItemDeserializer(), datamodel.Item.TYPE
		);
		this._strategyProvider.registerStrategy(
			new PropertyDeserializer(), datamodel.Property.TYPE
		);
	}, {
		/**
		 * @property {StrategyProvider}
		 * @private
		 */
		_strategyProvider: null,

		/**
		 * @param {Deserializer} deserializer
		 * @param {string} entityType
		 */
		registerStrategy: function( deserializer, entityType ) {
			this._strategyProvider.registerStrategy( deserializer, entityType );
		},

		/**
		 * @inheritdoc
		 *
		 * @return {datamodel.Entity}
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

}() );
