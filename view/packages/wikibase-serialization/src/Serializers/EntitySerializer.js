( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.EntitySerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.EntitySerializer = util.inherit( 'WbEntitySerializer', PARENT, function() {
	this._strategyProvider = new MODULE.StrategyProvider();
	this._strategyProvider.registerStrategy(
		new MODULE.ItemSerializer(), wb.datamodel.Item.TYPE
	);
	this._strategyProvider.registerStrategy(
		new MODULE.PropertySerializer(), wb.datamodel.Property.TYPE
	);
}, {
	/**
	 * @property {wikibase.serialization.StrategyProvider}
	 * @private
	 */
	_strategyProvider: null,

	/**
	 * @param {wikibase.serialization.Serializer} serializer
	 * @param {string} entityType
	 */
	registerStrategy: function( serializer, entityType ) {
		this._strategyProvider.registerStrategy( serializer, entityType );
	},

	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @return {Object}
	 *
	 * @throws {Error} if entity is not an Entity instance.
	 */
	serialize: function( entity ) {
		if( !( entity instanceof wb.datamodel.Entity ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Entity' );
		}

		return this._strategyProvider.getStrategyFor( entity.getType() ).serialize( entity );
	}
} );

}( wikibase, util ) );
