/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * Takes MediaWiki's mediawiki.config.get( 'wbUsedEntities' ) and builds the global local entity
 * store from that (wikibase.fetchedEntities).
 * Will invoked immediately after script inclusion. Exposing this function is for testing purposes
 * only.
 *
 * @private For testing the mechanism only.
 *
 * @since 0.5
 *
 * TODO: Should vanish together with wikibase.fetchedEntities as bug 54082 gets resolved.
 */
mediaWiki.ext.wikibase.repo.fetchedEntitiesFromMwConfig = ( function( $, wb, mwConfig ) {
	'use strict';

	var Entity = wb.Entity;
	var FetchedContent = wb.store.FetchedContent;
	var SerializerFactory = wb.serialization.SerializerFactory;

	function fetchedEntitiesFromMwConfig() {
		var unserializerFactory = new SerializerFactory();
		var entityUnserializer = unserializerFactory.newUnserializerFor( Entity );

		// unserializer for fetched content whose content is a wb.Entity:
		var fetchedEntityUnserializer = unserializerFactory.newUnserializerFor(
			FetchedContent, {
				contentUnserializer: entityUnserializer
			}
		);

		var serializedFetchedEntities = getSerializedFetchedEntitiesFromConfig( 'wbUsedEntities' );

		$.each( serializedFetchedEntities, function( id, serializedFetchedEntity ) {
			var fetchedEntity = fetchedEntityUnserializer.unserialize( serializedFetchedEntity );
			wb.fetchedEntities[ id ] = fetchedEntity;
		} );
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
			throw new Error( 'Can not load data for wikibase.fetchedEntities. mw.config variable "'
				+ varName + '" is not set.' );
		}
		return $.evalJSON( mwConfig.get( varName ) );
	}

	// Allows to simply add this resource loader module to the output.
	fetchedEntitiesFromMwConfig();

	return fetchedEntitiesFromMwConfig;

}( jQuery, wikibase, mediaWiki.config ) );
