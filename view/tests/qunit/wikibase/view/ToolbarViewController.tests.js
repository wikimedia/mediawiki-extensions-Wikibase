( function( $, sinon, QUnit, wb, mw ) {
'use strict';

QUnit.module( 'wikibase.view.ToolbarViewController' );

function initToolbarViewController() {
	var controller = new wb.view.ToolbarViewController(
		{
			remove: function() {
				return $.Deferred();
			},
			save: function() {
				return $.Deferred();
			}
		},
		{
			disable: function() {},
			enable: function() {},
			getButton: function() {
				return {
					disable: function() {},
					enable: function() {}
				};
			},
			toggleActionMessage: function() {},
			toEditMode: function() {},
			toNonEditMode: function() {},
		},
		{
			disable: function() {},
			element: {
				on: function() {}
			},
			enable: function() {},
			setError: function() {},
			startEditing: function() {},
			stopEditing: function() {},
			value: function() {}
		},
		function() {}
	);

	return controller;
}

wb.view.testViewController.all( wb.view.ToolbarViewController, initToolbarViewController );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
