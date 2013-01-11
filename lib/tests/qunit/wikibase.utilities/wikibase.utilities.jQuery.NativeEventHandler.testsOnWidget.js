/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function ( mw, wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Test definition for running NativeEventHandler tests within jQuery Widget environment,
	 * meaning, the jQuery.Widget's _trigger() function will be used to trigger events.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 * @extends wb.tests.NativeEventHandlerOnWidgetTestDefinition
	 */
	var TestDefinition =
			wb.tests.NativeEventHandlerOnWidgetTestDefinition =
			wb.utilities.inherit(
				wb.tests.NativeEventHandlerTestDefinition, {
		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.eventSystem
		 */
		eventSystem: 'jQuery.Widget.prototype._trigger',

		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.supportsCustomResults
		 */
		supportsCustomResults: false,

		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.newWidgetTestBody
		 */
		newTestBody: function() {
			var TestWidget = wb.utilities.inherit( $.Widget, {
				widgetName: 'neh_test_widget',
				widgetEventPrefix: 'neh_test_widget_'
			} );

			var testBody = new TestWidget( {}, $( '<div/>' ) );

			testBody.one = function( eventType, fn ) {
				// in widgets, event will have a prefix!
				testBody.element.one ( testBody.widgetEventPrefix + eventType, fn );
			};
			testBody.initialHandlerContext = testBody;
			testBody.customHandlerContext = testBody.element[0];
			testBody.nativeHandlerContext = testBody;

			return testBody;
		}
	} );

	// run tests:
	wb.tests.nativeEventHandlerTest( new TestDefinition() );

}( mediaWiki, wikibase, jQuery, QUnit ) );
