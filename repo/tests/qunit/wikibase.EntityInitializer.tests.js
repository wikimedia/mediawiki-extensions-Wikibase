( function ( QUnit, wb ) {
	'use strict';

	var EntityInitializer = wb.EntityInitializer;

	QUnit.module( 'wikibase.EntityInitializer' );

	QUnit.test( 'constructor validates parameter', function ( assert ) {
		try {
			new EntityInitializer(); // eslint-disable-line no-new
			assert.fail( 'Expected exception' );
		} catch ( e ) {
			assert.ok( e instanceof Error );
		}
	} );

	QUnit.test( 'can create instance from entity loaded hook', function ( assert ) {
		var initializer = EntityInitializer.newFromEntityLoadedHook();
		assert.ok( initializer instanceof EntityInitializer );
	} );

}( QUnit, jQuery.valueview, wikibase ) );
