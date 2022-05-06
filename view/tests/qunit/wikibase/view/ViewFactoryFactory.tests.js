( function ( wb ) {
	'use strict';

	var ViewFactoryFactory = require( '../../../../resources/wikibase/view/ViewFactoryFactory.js' ),
		sandbox = sinon.sandbox.create();

	QUnit.module( 'wikibase.view.ViewFactoryFactory', {
		beforeEach: function () {
			sandbox.stub( wb.view, 'ControllerViewFactory' );
			sandbox.stub( wb.view, 'ReadModeViewFactory' );
		},
		afterEach: function () {
			sandbox.restore();
		}
	} );

	QUnit.test( 'returns ControllerViewFactory when editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( true, [] );

		sinon.assert.calledWithNew( wb.view.ControllerViewFactory );
		assert.true( result instanceof wb.view.ControllerViewFactory );
	} );

	QUnit.test( 'returns ReadModeViewFactory when not editable', function ( assert ) {
		var factory = new ViewFactoryFactory(),
			result = factory.getViewFactory( false, [] );

		sinon.assert.calledWithNew( wb.view.ReadModeViewFactory );
		assert.true( result instanceof wb.view.ReadModeViewFactory );
	} );

	QUnit.test( 'ControllerViewFactory is called with correct arguments', function ( assert ) {
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( true, [ 1, 2, 3 ] );

		assert.true( wb.view.ControllerViewFactory.calledWith( 1, 2, 3 ) );
	} );

	QUnit.test( 'ReadModeViewFactory is called with correct arguments', function ( assert ) {
		var factory = new ViewFactoryFactory();

		factory.getViewFactory( false, [ 1, 2, 3 ] );

		assert.true( wb.view.ReadModeViewFactory.calledWith( 3 ) );
	} );

}( wikibase ) );
