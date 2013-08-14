/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function ( $, QUnit, runTest, testDefinitionBase ) {
	'use strict';

	/**
	 * Test definition for running NativeEventHandler tests within a plain Object's environment.
	 * For triggering events on the object, $( obj ).trigger() will be used.
	 *
	 * @type Object
	 */
	var testDefinition = $.extend( {}, testDefinitionBase, {
		/**
		 * @see jQuery.NativeEventHandler.test.testDefinition.eventSystem
		 */
		eventSystem: 'jQuery.fn.trigger',

		/**
		 * @see jQuery.NativeEventHandler.test.testDefinition.supportsCustomResults
		 */
		supportsCustomResults: true,

		/**
		 * @see jQuery.NativeEventHandler.test.testDefinition.newWidgetTestBody
		 */
		newTestBody: function() {
			var testBody = {
				one: function( eventType, fn ) {
					$( this ).one( eventType, fn );
				}
			};
			testBody.initialHandlerContext
				= testBody.customHandlerContext
				= testBody.nativeHandlerContext
				= testBody;

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
