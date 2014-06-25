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
			unserializerFactory = new wb.serialization.SerializerFactory(),
			entityUnserializer = unserializerFactory.newUnserializerFor( wb.Entity, wb.dataTypes );

		wb.entity = entityUnserializer.unserialize( entityJSON );
		entityJSON = null;
	} );

} )( jQuery, mediaWiki, wikibase );
