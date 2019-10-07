( function () {
	'use strict';
	var testViewController = require( './testViewController.js' ),
		ToolbarViewController = require( '../../../../resources/wikibase/view/ToolbarViewController.js' );

	QUnit.module( 'wikibase.view.ToolbarViewController' );

	function initToolbarViewController() {
		var controller = new ToolbarViewController(
			{
				remove: function () {
					return $.Deferred();
				},
				save: function () {
					return $.Deferred();
				}
			},
			{
				disable: function () {},
				enable: function () {},
				getButton: function () {
					return {
						disable: function () {},
						enable: function () {}
					};
				},
				toggleActionMessage: function () {},
				toEditMode: function () {},
				toNonEditMode: function () {}
			},
			{
				disable: function () {},
				element: {
					on: function () {}
				},
				enable: function () {},
				setError: function () {},
				startEditing: function () {
					return $.Deferred();
				},
				stopEditing: function () {},
				value: function () {}
			},
			function () {}
		);

		return controller;
	}

	testViewController.all( ToolbarViewController, initToolbarViewController );

}() );
