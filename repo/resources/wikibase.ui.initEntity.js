/**
 * Entity initialization.
 * Unserializes the entities passed to JavaScript via mw.config variables.
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author: H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
	'use strict';

	mw.hook( 'wikipage.content' ).add( function() {

		if( mw.config.get( 'wbEntity' ) === null ) {
			return;
		}

		var entityJSON = $.evalJSON( mw.config.get( 'wbEntity' ) ),
			usedEntitiesJSON = $.evalJSON( mw.config.get( 'wbUsedEntities' ) ),
			unserializerFactory = new wb.serialization.SerializerFactory(),
			entityUnserializer = unserializerFactory.newUnserializerFor( wb.Entity );

		// Unserializer for fetched content whose content is a wb.Entity:
		var fetchedEntityUnserializer = unserializerFactory.newUnserializerFor(
			wb.store.FetchedContent,
			{ contentUnserializer: entityUnserializer }
		);

		wb.entity = entityUnserializer.unserialize( entityJSON );
		entityJSON = null;

		$.each( usedEntitiesJSON, function( id, fetchedEntityJSON ) {
			wb.fetchedEntities[id] = fetchedEntityUnserializer.unserialize( fetchedEntityJSON );
		} );

	} );

} )( jQuery, mediaWiki, wikibase );