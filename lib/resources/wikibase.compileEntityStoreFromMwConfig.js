/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
wikibase.compileEntityStoreFromMwConfig = ( function( $, wb, mw ) {
	'use strict';

	/**
	 * Fetched entity unserializer's unserialize function.
	 * @type {Function}
	 */
	var unserialize;

	/**
	 * Compiles an EntityStore object from MediaWiki's "wbUsedEntities" config variable.
	 * @since 0.5
	 *
	 * @param {wikibase.store.EntityStore} entityStore The EntityStore to compile.
	 */
	function compileEntityStoreFromMwConfig( entityStore ) {
		var serializedFetchedEntities,
			fetchedEntities;

		serializedFetchedEntities = getSerializedFetchedEntitiesFromConfig( 'wbUsedEntities' );

		if( !$.isEmptyObject( serializedFetchedEntities ) ) {
			fetchedEntities = mapObj( serializedFetchedEntities, getUnserialize() );
			entityStore.compile( fetchedEntities );
		}
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
		if( !mw.config.exists( varName ) ) {
			throw new Error( 'Can not load data for wikibase.compileEntityStoreFromMwConfig. '
				+ 'mw.config variable "' + varName + '" is not set.' );
		}
		return $.evalJSON( mw.config.get( varName ) );
	}

	/**
	 * Applies a function to all values of an object.
	 *
	 * @param {Object} obj
	 * @param {Function} mapper
	 * @return {Object}
	 *
	 * @todo Move to utils?
	 */
	function mapObj( obj, mapper ) {
		var res = {};
		$.each( obj, function( k, v ) { res[k] = mapper( v ); } );
		return res;
	}

	/**
	 * Returns the "unserialize" function of the fetched entity unserializer.
	 *
	 * @return {Function}
	 */
	function getUnserialize() {
		if( !unserialize ) {
			var factory = new wb.serialization.SerializerFactory(),
				entityUnserializer = factory.newUnserializerFor( wb.Entity, wb.dataTypes );

			// Unserializer for fetched content whose content is a wb.Entity:
			var fetchedEntityUnserializer = factory.newUnserializerFor(
				wb.store.FetchedContent, {
					contentUnserializer: entityUnserializer
				}
			);

			unserialize = $.proxy( fetchedEntityUnserializer, 'unserialize' );
		}
		return unserialize;
	}

	return compileEntityStoreFromMwConfig;

}( jQuery, wikibase, mediaWiki ) );
