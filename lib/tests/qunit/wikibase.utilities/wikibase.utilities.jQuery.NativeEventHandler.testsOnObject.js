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
	 * Test definition for running NativeEventHandler tests within a normal Object's environment.
	 * For triggering events on the object, $( obj ).trigger() will be used.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 * @extends wb.tests.NativeEventHandlerTestDefinition
	 */
	var TestDefinition =
			wb.tests.NativeEventHandlerOnObjectTestDefinition =
			wb.utilities.inherit(
				wb.tests.NativeEventHandlerTestDefinition, {
		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.eventSystem
		 */
		eventSystem: 'jQuery.fn.trigger',

		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.supportsCustomResults
		 */
		supportsCustomResults: true,

		/**
		 * @see wb.tests.NativeEventHandlerTestDefinition.newWidgetTestBody
		 */
		newTestBody: function() {
			var testBody = {
				one: function( eventType, fn ) {
					$( this ).one( eventType, fn );
				}
			};
			testBody.initialHandlerContext = testBody.customHandlerContext =
				testBody.nativeHandlerContext = testBody;

			return testBody;
		}
	} );

	// run tests:
	wb.tests.nativeEventHandlerTest( new TestDefinition() );

}( mediaWiki, wikibase, jQuery, QUnit ) );
