( function ( $, sinon, QUnit, wb, ViewFactoryFactory ) {
	'use strict';

	var sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.view.ViewFactoryFactory', {
		beforeEach: function () {
			sandbox.stub( wikibase.view, 'ControllerViewFactory' );
			sandbox.stub( wikibase.view, 'ReadModeViewFactory' );
		},
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'returns ControllerViewFactory when editable', function ( assert ) {
		assert.expect( 2 );
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( true, [] );

		sinon.assert.calledWithNew( wikibase.view.ControllerViewFactory );
		assert.ok( result instanceof wikibase.view.ControllerViewFactory );
	} );

	QUnit.test( 'returns ReadModeViewFactory when not editable', function ( assert ) {
		assert.expect( 2 );
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( false, [] );

		sinon.assert.calledWithNew( wikibase.view.ReadModeViewFactory );
		assert.ok( result instanceof wikibase.view.ReadModeViewFactory );
	} );

	QUnit.test( 'ControllerViewFactory is called with correct arguments', function ( assert ) {
		assert.expect( 1 );
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( true, [ 1, 2, 3 ] );

		assert.ok( wikibase.view.ControllerViewFactory.calledWith( 1, 2, 3 ) );
	} );

	QUnit.test( 'ReadModeViewFactory is called with correct arguments', function ( assert ) {
		assert.expect( 1 );
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( false, [ 1, 2, 3 ] );

		assert.ok( wikibase.view.ReadModeViewFactory.calledWith( 3 ) );
	} );

}( jQuery, sinon, QUnit, wikibase, wikibase.view.ViewFactoryFactory ) );
