( function () {
	'use strict';

	var ToolbarFactory = require( '../../../../resources/wikibase/view/ToolbarFactory.js' );

	QUnit.module( 'wikibase.view.ToolbarFactory' );

	QUnit.test( 'is constructable', function ( assert ) {
		assert.true( new ToolbarFactory() instanceof ToolbarFactory );
	} );

	QUnit.test( 'getToolbarContainer returns the first container', function ( assert ) {
		var toolbarFactory = new ToolbarFactory(),
			$root = $( '<div>' );

		$root.html( '<div/><div class="wikibase-toolbar-container"/><div class="wikibase-toolbar-container"/>' );

		var res = toolbarFactory.getToolbarContainer( $root );

		assert.strictEqual( res.length, 1 );
		assert.strictEqual( res[ 0 ], $root.children()[ 1 ] );
	} );

	QUnit.test( 'getToolbarContainer returns a new container for an empty element', function ( assert ) {
		var toolbarFactory = new ToolbarFactory(),
			$root = $( '<div>' ).html( '<div>' );

		var res = toolbarFactory.getToolbarContainer( $root );

		assert.strictEqual( $root.children().length, 2 );
		assert.strictEqual( res[ 0 ], $root.children()[ 1 ] );
	} );

	QUnit.test( 'getToolbarContainer does not return subchildren container', function ( assert ) {
		var toolbarFactory = new ToolbarFactory(),
			$root = $( '<div>' ).html( '<div><div class="wikibase-toolbar-container"/></div>' );

		var res = toolbarFactory.getToolbarContainer( $root );

		assert.strictEqual( $root.children().length, 2 );
		assert.strictEqual( res[ 0 ], $root.children()[ 1 ] );
	} );

	QUnit.test( 'getEditToolbar creates an edit toolbar', function ( assert ) {
		var toolbarFactory = new ToolbarFactory(),
			stub = sinon.stub( $.wikibase, 'edittoolbar' ),
			$root = $( '<div>' );

		$root.edittoolbar = stub;

		toolbarFactory.getEditToolbar( {}, $root );

		sinon.assert.calledOn( stub, $root );

		stub.restore();
	} );
	QUnit.test( 'getEditToolbar passes options', function ( assert ) {
		var toolbarFactory = new ToolbarFactory(),
			stub = sinon.stub( $.wikibase, 'edittoolbar' ),
			$root = $( '<div>' ),
			options = {};

		$root.edittoolbar = stub;

		toolbarFactory.getEditToolbar( options, $root );

		sinon.assert.calledWith( stub, options );

		stub.restore();
	} );

}() );
