( function ( $, mw, wb ) {
	'use strict';

	var entityPromise = $.Deferred( function ( deferred ) {
		mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
			deferred.resolve( entity );
		} );
	} ).promise();

	var entityInitializer = new wb.EntityInitializer( entityPromise );

	wb.entityInit = entityInitializer.getEntity();

}(
	jQuery,
	mediaWiki,
	wikibase
) );
