/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function ( $, QUnit, runTest, testDefinitionBase ) {
	'use strict';

	/**
	 * Test definition for running NativeEventHandler tests within jQuery Widget environment,
	 * meaning, the jQuery.Widget's _trigger() function will be used to trigger events.
	 *
	 * @type Object
	 */
	var testDefinition = $.extend( {}, testDefinitionBase, {
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
			var TestWidget = function() {
				$.Widget.apply( this, arguments );
			};
			TestWidget.prototype = $.extend( new $.Widget(), {
				constructor: TestWidget,
				widgetName: 'neh_test_widget',
				widgetEventPrefix: 'neh_test_widget_'
			} );

			var testBody = new TestWidget( {}, $( '<div/>' ) );

			testBody.one = function( eventType, fn ) {
				// In widgets, event will have a prefix!
				testBody.element.one( testBody.widgetEventPrefix + eventType, fn );
			};
			testBody.initialHandlerContext = testBody;
			testBody.customHandlerContext = testBody.element[0];
			testBody.nativeHandlerContext = testBody;

			return testBody;
		}
	} );

	runTest( testDefinition );
}(
	jQuery,
	QUnit,
	jQuery.NativeEventHandler.test,
	jQuery.NativeEventHandler.test.testDefinition
) );
