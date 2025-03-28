( function ( wb ) {
	'use strict';

	var EntityInitializer = wb.EntityInitializer;

	QUnit.module( 'wikibase.EntityInitializer' );

	QUnit.test( 'constructor validates parameter', ( assert ) => {
		try {
			new EntityInitializer(); // eslint-disable-line no-new
			assert.fail( 'Expected exception' );
		} catch ( e ) {
			assert.true( e instanceof Error );
		}
	} );

	QUnit.test( 'can create instance from entity loaded hook', ( assert ) => {
		var initializer = EntityInitializer.newFromEntityLoadedHook();
		assert.true( initializer instanceof EntityInitializer );
	} );

	QUnit.test( 'uses entity returned from hook', function ( assert ) {
		var done = assert.async(),
			entity = { id: 'Q123' },
			hookStub = this.sandbox.stub( mw, 'hook' ).returns( {
				add: sinon.stub().yields( entity )
			} ),
			mockDeserializer = {
				deserialize: sinon.stub()
			};

		this.sandbox.stub( EntityInitializer.prototype, '_getDeserializer' )
			.returns( $.Deferred().resolve( mockDeserializer ) );

		var initializer = EntityInitializer.newFromEntityLoadedHook();

		initializer.getEntity().then( () => {
			assert.true( hookStub.calledWith( 'wikibase.entityPage.entityLoaded' ) );
			assert.true( mockDeserializer.deserialize.calledWith( entity ) );
			done();
		} );
	} );

}( wikibase ) );
