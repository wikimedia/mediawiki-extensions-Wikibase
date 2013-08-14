/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.NativeEventHandler.test.testDefinition = ( function ( $, QUnit ) {
	'use strict';

	/**
	 * Test definition for running wb.tests.nativeEventHandlerTest with.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @abstract
	 */
	return {
		/**
		 * Descriptive name of the event handler system which is used together with the
		 * NativeEventHandler in this test definition.
		 * @type {String}
		 */
		eventSystem: '',

		/**
		 * Whether custom results are supported by the event handler system in use. E.g. $.Widget's
		 * _trigger() does not allow for custom results while $.trigger() does.
		 * @type Boolean
		 */
		supportsCustomResults: false,

		/**
		 * Returns an Object which test functions factored by the NativeEventHandler can be attached
		 * to. The returned object also has a 'one' function which is equivalent to jQuery.one() and
		 * allows for listening to events which should be triggered by any NativeEventHandler
		 * defined on the test body Object returned by this.
		 *
		 * @return {Object} Will have the following fields:
		 *         - 'one' function to register custom event handler, will be removed after called once.
		 *         - 'initialHandlerContext' The object which should be the context for initial handler.
		 *         - 'customHandlerContext' The object which should be the context for custom handlers.
		 *         - 'nativeHandlerContext' The object which should be the context for native handler.
		 */
		newWidgetTestBody: function() {
			throw new Error( '"newWidgetTestBody" has to be overwritten in specific test '
				+ 'implementation' );
		}
	};

}( jQuery, QUnit ) );
