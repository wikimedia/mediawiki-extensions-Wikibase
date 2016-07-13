( function( $, sinon, QUnit, wb, mw ) {
'use strict';

QUnit.module( 'wikibase.view.ToolbarViewController' );

function initToolbarViewController() {
	var controller = new wb.view.ToolbarViewController(
		{
			save: function() {
				return $.Deferred();
			},
			remove: function() {
				return $.Deferred();
			}
		},
		{
			disable: function() {},
			enable: function() {},
			getButton: function() {
				return {
					enable: function() {},
					disable: function() {}
				};
			},
			toEditMode: function() {},
			toNonEditMode: function() {},
			toggleActionMessage: function() {}
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
