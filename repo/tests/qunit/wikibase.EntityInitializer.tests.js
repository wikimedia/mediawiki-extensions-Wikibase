( function ( QUnit, wb, sinon, mw ) {
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

	QUnit.test( 'uses entity returned from hook', function ( assert ) {
		var done = assert.async(),
			entityLoadedStub = sinon.stub(),
			hookStub = sinon.stub( mw, 'hook' ),
			deserialize = sinon.stub(),
			mockDeserializer = {
				deserialize: deserialize
			},
			entity = { id: 'Q123' };

		hookStub.returns( {
			add: entityLoadedStub
		} );

		var deserializerStub = sinon.stub( EntityInitializer.prototype, '_getDeserializer', function () {
			return $.Deferred().resolve( mockDeserializer );
		} );

		var initializer = EntityInitializer.newFromEntityLoadedHook();
		entityLoadedStub.yield( entity );

		initializer.getEntity().then( function () {
			assert.ok( hookStub.calledWith( 'wikibase.entityPage.entityLoaded' ) );
			assert.ok( deserialize.calledWith( entity ) );
			done();
		} );

		hookStub.restore();
		deserializerStub.restore();
	} );

}( QUnit, wikibase, sinon, mediaWiki ) );
