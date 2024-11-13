module.exports = ( function ( QUnit, wb ) {
	'use strict';

	return {
		all: function ( constructor, getInstance ) {
			this.constructorTests( constructor, getInstance );
			this.methodTests( getInstance );
		},

		constructorTests: function ( constructor, getInstance ) {
			QUnit.test( 'implements wb.view.ViewController', ( assert ) => {
				var controller = getInstance();

				assert.true( controller instanceof constructor );
			} );
		},

		methodTests: function ( getInstance ) {
			QUnit.test( 'has non-abstract startEditing method', ( assert ) => {
				var controller = getInstance();

				controller.startEditing();

				assert.true( true );
			} );

			QUnit.test( 'has non-abstract stopEditing method', ( assert ) => {
				var controller = getInstance();

				controller.stopEditing();

				assert.true( true );
			} );

			QUnit.test( 'has non-abstract cancelEditing method', ( assert ) => {
				var controller = getInstance();

				controller.cancelEditing();

				assert.true( true );
			} );

			QUnit.test( 'has non-abstract setError method', ( assert ) => {
				var controller = getInstance();

				controller.setError();

				assert.true( true );
			} );

			QUnit.test( 'has non-abstract remove method', ( assert ) => {
				var controller = getInstance();

				controller.remove();

				assert.true( true );
			} );
		}

	};

}( QUnit, wikibase ) );
