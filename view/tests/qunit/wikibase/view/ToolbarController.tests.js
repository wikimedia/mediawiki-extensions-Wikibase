( function( $, sinon, QUnit, wb, mw ) {
'use strict';

QUnit.module( 'wikibase.view.ToolbarController' );

function initToolbarController() {
	var controller = new wb.view.ToolbarController(
		null,
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
			stopEditing: function() {}
		}
	);

	return controller;
}

wb.view.testController.all( wb.view.ToolbarController, initToolbarController );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
