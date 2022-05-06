( function ( wb ) {
	'use strict';

	var EntityInitializer = wb.EntityInitializer,
		sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.EntityInitializer', {
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'constructor validates parameter', function ( assert ) {
		try {
			new EntityInitializer(); // eslint-disable-line no-new
			assert.fail( 'Expected exception' );
		} catch ( e ) {
			assert.true( e instanceof Error );
		}
	} );

	QUnit.test( 'can create instance from entity loaded hook', function ( assert ) {
		var initializer = EntityInitializer.newFromEntityLoadedHook();
		assert.true( initializer instanceof EntityInitializer );
	} );

	QUnit.test( 'uses entity returned from hook', function ( assert ) {
		var done = assert.async(),
			entity = { id: 'Q123' },
			hookStub = sandbox.stub( mw, 'hook' ).returns( {
				add: sinon.stub().yields( entity )
			} ),
			mockDeserializer = {
				deserialize: sinon.stub()
			};

		sandbox.stub( EntityInitializer.prototype, '_getDeserializer' )
			.returns( $.Deferred().resolve( mockDeserializer ) );

		var initializer = EntityInitializer.newFromEntityLoadedHook();

		initializer.getEntity().then( function () {
			assert.true( hookStub.calledWith( 'wikibase.entityPage.entityLoaded' ) );
			assert.true( mockDeserializer.deserialize.calledWith( entity ) );
			done();
		} );
	} );

}( wikibase ) );
