( function( $, sinon, QUnit, wb, mw ) {
'use strict';

QUnit.module( 'wikibase.view.ToolbarViewController' );

function initToolbarViewController() {
	var controller = new wb.view.ToolbarViewController(
		{
			remove: function() {
				return $.Deferred();
			}
		},
		{
			disable: function() {},
			enable: function() {},
			getButton: function() {
				return {
					disable: function() {}
				};
			},
			toEditMode: function() {},
			toNonEditMode: function() {}
		},
		{
			disable: function() {},
			element: {
				on: function() {}
			},
			enable: function() {},
			isValid: function() {},
			setError: function() {},
			startEditing: function() {},
			stopEditing: function() {},
			value: function() {}
		}
	);

	return controller;
}

wb.view.testViewController.all( wb.view.ToolbarViewController, initToolbarViewController );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
