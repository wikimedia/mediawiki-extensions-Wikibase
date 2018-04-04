( function ( $, sinon, QUnit, wb, ViewFactoryFactory ) {
	'use strict';

	QUnit.module( 'wikibase.view.ViewFactoryFactory' );

	QUnit.test( 'returns ControllerViewFactory when editable', function ( assert ) {
		assert.expect( 2 );
		var factory = new ViewFactoryFactory(),
			controllerViewStub = sinon.stub( wikibase.view, 'ControllerViewFactory' ),
			result = factory.getViewFactory( true, [] );

		sinon.assert.calledWithNew( controllerViewStub );
		assert.ok( result instanceof controllerViewStub );

		controllerViewStub.restore();
	} );

	QUnit.test( 'returns ReadModeViewFactory when not editable', function ( assert ) {
		assert.expect( 2 );
		var factory = new ViewFactoryFactory(),
			readModeViewFactory = sinon.stub( wikibase.view, 'ReadModeViewFactory' ),
			result = factory.getViewFactory( false, [] );

		sinon.assert.calledWithNew( readModeViewFactory );
		assert.ok( result instanceof readModeViewFactory );

		readModeViewFactory.restore();
	} );

	QUnit.test( 'ControllerViewFactory is called with correct arguments', function ( assert ) {
		assert.expect( 1 );
		var factory = new ViewFactoryFactory(),
			controllerViewStub = sinon.stub( wikibase.view, 'ControllerViewFactory' );

		factory.getViewFactory( true, [ 1, 2, 3 ] );

		assert.ok( controllerViewStub.calledWith( 1, 2, 3 ) );

		controllerViewStub.restore();
	} );

	QUnit.test( 'ReadModeViewFactory is called with correct arguments', function ( assert ) {
		assert.expect( 1 );
		var factory = new ViewFactoryFactory(),
			readModeViewStub = sinon.stub( wikibase.view, 'ReadModeViewFactory' );

		factory.getViewFactory( false, [ 1, 2, 3 ] );

		assert.ok( readModeViewStub.calledWith( 3 ) );

		readModeViewStub.restore();
	} );

}( jQuery, sinon, QUnit, wikibase, wikibase.view.ViewFactoryFactory ) );
