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
	this._strategyProvider = new MODULE.StrategyProvider();
	this._strategyProvider.registerStrategy(
		new MODULE.ItemSerializer(), wb.datamodel.Item.TYPE
	);
	this._strategyProvider.registerStrategy(
		new MODULE.PropertySerializer(), wb.datamodel.Property.TYPE
	);
}, {
	/**
	 * @type {wikibase.serialization.StrategyProvider}
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
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Entity} entity
	 * @return {Object}
	 */
	serialize: function( entity ) {
		if( !( entity instanceof wb.datamodel.Entity ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Entity' );
		}

		return this._strategyProvider.getStrategyFor( entity.getType() ).serialize( entity );
	}
} );

}( wikibase, util ) );
