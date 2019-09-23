/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function () {
	'use strict';

	var CombiningEntityStore = require( '../../../../resources/wikibase/store/store.CombiningEntityStore.js' ),
		EntityStore = require( '../../../../resources/wikibase/store/store.EntityStore.js' );

	QUnit.module( 'wikibase.store.CombiningEntityStore' );

	QUnit.test( 'Initialize', function ( assert ) {
		var entityStore = new CombiningEntityStore();
		assert.ok( entityStore.get, 'Entity store has get() method.' );
	} );

	QUnit.test( 'get() returns a jQuery promise', function ( assert ) {
		var entityStore = new CombiningEntityStore( [] ),
			promise = entityStore.get( 'id' );

		assert.ok( promise.done, 'done() method exists.' );
	} );

	QUnit.test(
		'Promise is resolved asynchronously, even if the entity is cached',
		function ( assert ) {
			var store = new EntityStore();
			store.get = function ( entityId ) {
				return $.Deferred().resolve();
			};
			var entityStore = new CombiningEntityStore( [ store ] );

			var promise = entityStore.get( 'id' );
			assert.strictEqual( promise.state(), 'pending', 'Promise is pending.' );

			return promise.done( function ( entity ) {
				assert.ok( true, 'Resolved promise.' );
			} );
		}
	);

}() );
