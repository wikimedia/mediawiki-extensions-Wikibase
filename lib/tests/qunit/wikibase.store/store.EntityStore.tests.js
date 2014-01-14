/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb ) {
	'use strict';

	QUnit.module( 'wikibase.store.EntityStore', window.QUnit.newWbEnvironment( {
	} ) );

	QUnit.test( 'Initialize', function( assert ) {
		var entityStore = new wb.store.EntityStore();
		assert.ok( entityStore.get, 'has get method' );
	} );

	QUnit.test( 'get returns promise', function( assert ) {
		var entityStore = new wb.store.EntityStore();
		var promise = entityStore.get( 'id' );
		assert.ok( promise.done, 'has done method' );
	} );

	QUnit.asyncTest( 'promise is resolved asynchronously even if the entity is in cache', function( assert ) {
		QUnit.expect( 2 );
		QUnit.start();

		var entityStore = new wb.store.EntityStore();
		entityStore.seed( {
			id: 'value'
		} );

		var promise = entityStore.get( 'id' );
		assert.equal( promise.state(), 'pending' );

		QUnit.stop();
		promise.done( function( entity ) {
			QUnit.start();
			assert.ok( true );
		} );
	} );

} )( jQuery, mediaWiki, wikibase );
