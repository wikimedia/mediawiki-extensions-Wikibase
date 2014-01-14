/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 *
 * Takes MediaWiki's mediawiki.config.get( 'wbUsedEntities' ) and seeds the passed
 * EntityStore from that (wikibase.seedEntityStore).
 *
 * @param {wikibase.store.EntityStore} entityStore The EntityStore to seed
 */
wikibase.seedEntityStoreFromMwConfig = ( function( $, wb, mwConfig ) {
	'use strict';

	var getUnserialize = (function () {
		var factory, entityUnserializer, fetchedEntityUnserializer, unserialize;

		return function () {
			if( !fetchedEntityUnserializer ) {
				factory = new wb.serialization.SerializerFactory();
				entityUnserializer = factory.newUnserializerFor( wb.Entity );

				// unserializer for fetched content whose content is a wb.Entity:
				fetchedEntityUnserializer = factory.newUnserializerFor(
					wb.store.FetchedContent, {
						contentUnserializer: entityUnserializer
					}
				);

				unserialize = $.proxy( fetchedEntityUnserializer, 'unserialize' );
			}
			return unserialize;
		};
	}());

	function seedEntityStoreFromMwConfig( entityStore ) {
		var serializedFetchedEntities = getSerializedFetchedEntitiesFromConfig( 'wbUsedEntities' );
		var fetchedEntities = $.map( serializedFetchedEntities, getUnserialize() );

		entityStore.seed( fetchedEntities );
	}

	/**
	 * Helper for getting the serialized fetched entities in object form out of mw.config.
	 *
	 * @param {string} varName mw.config var name
	 * @return {Object}
	 *
	 * @throws {Error} In case the given config variable name does not exist.
	 */
	function getSerializedFetchedEntitiesFromConfig( varName ) {
		if( !mwConfig.exists( varName ) ) {
			throw new Error( 'Can not load data for wikibase.seedEntityStore. mw.config variable "'
				+ varName + '" is not set.' );
		}
		return $.evalJSON( mwConfig.get( varName ) );
	}

	return seedEntityStoreFromMwConfig;

}( jQuery, wikibase, mediaWiki.config ) );
