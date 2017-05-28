wikibase.view.testViewController = ( function ( QUnit, wb ) {
	'use strict';

	return {
		all: function ( constructor, getInstance ) {
			this.constructorTests( constructor, getInstance );
			this.methodTests( getInstance );
		},

		constructorTests: function ( constructor, getInstance ) {
			QUnit.test( 'implements wb.view.ViewController', function ( assert ) {
				assert.expect( 2 );
				var controller = getInstance();

				assert.ok( controller instanceof constructor );
				assert.ok( controller instanceof wb.view.ViewController );
			} );
		},

		methodTests: function ( getInstance ) {
			QUnit.test( 'has non-abstract startEditing method', function ( assert ) {
				assert.expect( 1 );
				var controller = getInstance();

				controller.startEditing();

				assert.ok( true );
			} );

			QUnit.test( 'has non-abstract stopEditing method', function ( assert ) {
				assert.expect( 1 );
				var controller = getInstance();

				controller.stopEditing();

				assert.ok( true );
			} );

			QUnit.test( 'has non-abstract cancelEditing method', function ( assert ) {
				assert.expect( 1 );
				var controller = getInstance();

				controller.cancelEditing();

				assert.ok( true );
			} );

			QUnit.test( 'has non-abstract setError method', function ( assert ) {
				assert.expect( 1 );
				var controller = getInstance();

				controller.setError();

				assert.ok( true );
			} );

			QUnit.test( 'has non-abstract remove method', function ( assert ) {
				assert.expect( 1 );
				var controller = getInstance();

				controller.remove();

				assert.ok( true );
			} );
		}

	};

}( QUnit, wikibase ) );
