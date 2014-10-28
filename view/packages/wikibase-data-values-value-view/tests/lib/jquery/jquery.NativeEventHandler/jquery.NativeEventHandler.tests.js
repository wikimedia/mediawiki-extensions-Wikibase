/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function ( $, QUnit, runTest, testDefinitionBase ) {
	'use strict';

	var testDefinition;

	/**
	 * Test definition for running NativeEventHandler tests within a plain Object's environment.
	 * For triggering events on the object, $( obj ).trigger() will be used.
	 *
	 * @type Object
	 */
	testDefinition = $.extend( {}, testDefinitionBase, {
		/**
		 * @see jQuery.NativeEventHandler.test.testDefinition.eventSystem
		 */
		eventSystem: 'jQuery.trigger',

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

	/**
	 * Test definition for running NativeEventHandler tests within jQuery Widget environment,
	 * meaning, the jQuery.Widget's _trigger() function will be used to trigger events.
	 *
	 * @type Object
	 */
	testDefinition = $.extend( {}, testDefinitionBase, {
		/**
		 * @see jQuery.NativeEventHandlerTestDefinition.test.eventSystem
		 */
		eventSystem: 'jQuery.Widget.prototype._trigger',

		/**
		 * @see jQuery.NativeEventHandlerTestDefinition.test.supportsCustomResults
		 */
		supportsCustomResults: false,

		/**
		 * @see jQuery.NativeEventHandlerTestDefinition.test.newWidgetTestBody
		 */
		newTestBody: function() {

			// FIXME: Move to a separate module
			var SubclassableWidget = function( options, element ) {
				this._childConstructors = [];
				this._createWidget( options, element );
				$.Widget.apply( this, arguments );
			};

			SubclassableWidget.prototype = $.extend( new $.Widget(), {
				widgetFullName: 'widget' // Needs to be set for _createWidget to pass
			} );

			function getWidgetSubclass( namespace, widgetName, constructor ) {
				var PARENT = SubclassableWidget;

				constructor = constructor || function( options, element ) {
					PARENT.call( this, options, element );
				};

				constructor.prototype = $.extend( new PARENT(), {
					constructor: constructor,
					namespace: namespace,
					widgetName: widgetName,
					widgetFullName: namespace + '.' + widgetName,
					widgetEventPrefix: namespace + '_' + widgetName + '_'
				} );

				return constructor;
			}

			var TestWidget = getWidgetSubclass( 'neh_test', 'widget' );

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

}( jQuery, QUnit, jQuery.NativeEventHandler.test, jQuery.NativeEventHandler.test.testDefinition ) );
