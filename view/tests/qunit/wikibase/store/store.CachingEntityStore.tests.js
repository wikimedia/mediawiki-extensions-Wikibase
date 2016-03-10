/**
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
( function( $, wb, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.store.CachingEntityStore' );

	QUnit.test( 'Initialize', function( assert ) {
		assert.expect( 1 );
		var entityStore = new wb.store.CachingEntityStore();
		assert.ok( entityStore.get, 'Entity store has get() method.' );
	} );

	QUnit.test( 'get() returns a jQuery promise', function( assert ) {
		assert.expect( 1 );
		var store = new wb.store.EntityStore();
		store.get = function( entityId ) {
			return $.Deferred().resolve();
		};
		var entityStore = new wb.store.CachingEntityStore( store ),
			promise = entityStore.get( 'id' );

		assert.ok( promise.done, 'done() method exists.' );
	} );

	QUnit.test( 'upstream store is called', 2, function( assert ) {
		var store = new wb.store.EntityStore();
		store.get = sinon.spy( function( entityId ) {
			return $.Deferred().resolve();
		} );
		var entityStore = new wb.store.CachingEntityStore( store );

		var promise = entityStore.get( 'id' );

		QUnit.stop();
		promise.done( function( entity ) {
			QUnit.start();
			sinon.assert.calledOnce( store.get );
			sinon.assert.calledWith( store.get, 'id' );
		} );
	} );

	QUnit.test( 'upstream store is called once', 1, function( assert ) {
		var store = new wb.store.EntityStore();
		store.get = sinon.spy( function( entityId ) {
			return $.Deferred().resolve();
		} );
		var entityStore = new wb.store.CachingEntityStore( store );

		var promise = entityStore.get( 'id' );

		QUnit.stop();
		promise.done( function( entity ) {
			var promise = entityStore.get( 'id' );

			promise.done( function( entity ) {
				QUnit.start();
				sinon.assert.calledOnce( store.get );
			} );
		} );
	} );

	QUnit.test( 'upstream store is called once for parallel calls', 1, function( assert ) {
		var store = new wb.store.EntityStore();
		store.get = sinon.spy( function( entityId ) {
			var deferred = $.Deferred();
			setTimeout( function() {
				deferred.resolve();
			}, 0 );
			return deferred.promise();
		} );
		var entityStore = new wb.store.CachingEntityStore( store );

		var promise1 = entityStore.get( 'id' );
		var promise2 = entityStore.get( 'id' );

		QUnit.stop();
		$.when( promise1, promise2 ).done( function() {
			QUnit.start();
			sinon.assert.calledOnce( store.get );
		} );
	} );

} )( jQuery, wikibase, sinon );
