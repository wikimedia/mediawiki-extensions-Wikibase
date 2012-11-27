/**
 * QUnit tests for Wikibase jQuery extension 'NativeEventHandler'
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function ( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.NativeEventHandler', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

var // TEST HELPERS:
	NEH_STAGE = {
		INITIAL: 1,
		CUSTOM: 2,
		NATIVE: 4
	},
	testResult = 0,
	initialHandler = function() {
		testResult |= NEH_STAGE.INITIAL;
	},
	customHandler = function() {
		testResult |= NEH_STAGE.CUSTOM;
	},
	nativeHandler = function() {
		testResult |= NEH_STAGE.NATIVE;
	},
	testNEH = function( exceptedFlag, comment ) {
		QUnit.assert.equal(
			testResult,
			exceptedFlag,
			comment
		);
		testResult = 0;
	};

	// ACTUAL TESTS:

	QUnit.test( 'Simple native event', function( assert ) {
		var TEST_EVENT = 'run',
			testObj = {},
			ret;

		testObj[ TEST_EVENT ] = $.NativeEventHandler( TEST_EVENT, nativeHandler );

		assert.ok(
			$.isFunction( testObj[ TEST_EVENT ] ),
			'Returns a function'
		);

		assert.ok(
			$.isFunction( testObj[ TEST_EVENT ].nativeHandler ),
			'Reference to inner native handler function stored'
		);

		// register some custom event
		$( testObj ).one( TEST_EVENT, customHandler );
		testObj[ TEST_EVENT ](); // CALL!
		testNEH(
			NEH_STAGE.NATIVE + NEH_STAGE.CUSTOM,
			'custom and native events were called after registering event with jQuery.one()'
		);

		testObj[ TEST_EVENT ](); // CALL!
		testNEH(
			NEH_STAGE.NATIVE,
			'only native handler was called because no events are registered'
		);

		$( testObj ).one( TEST_EVENT, function( event ) {
			customHandler();
			event.preventDefault(); // should prevent from calling native handler
			return 'foo';
		} );
		ret = testObj[ TEST_EVENT ](); // CALL!
		testNEH(
			NEH_STAGE.CUSTOM,
			'only custom handlers are called after one of them requests jQuery.Event.preventDefault()'
		);
		assert.notEqual(
			ret,
			'foo', // shouldn't work because allowCustomResult not set to true!
			'calling event function has not returned value returned by native handler'
		);

		$( testObj ).one( TEST_EVENT, function( event ) {
			event.stopImmediatePropagation();
		} );
		$( testObj ).one( TEST_EVENT, customHandler );
		testObj[ TEST_EVENT ](); // CALL!
		testNEH(
			NEH_STAGE.NATIVE,
			'no further custom handlers were called after jQuery.Event.stopImmediatePropagation()'
		);
	} );


	QUnit.test( 'Simple native event with initial handler, also allowing custom results', function( assert ) {
		var TEST_EVENT = 'run',
			testObj = {},
			ret;

		testObj[ TEST_EVENT ] = $.NativeEventHandler( TEST_EVENT, {
			allowCustomResult: true,
			initially: function( event, cancel ) {
				initialHandler();
				if( cancel ) { // for cancel test
					event.cancel();
				}
				return NEH_STAGE.INITIAL;
			},
			natively: function( event ) {
				nativeHandler();
				return NEH_STAGE.NATIVE;
			}
		} );

		assert.ok(
			$.isFunction( testObj[ TEST_EVENT ].initialHandler ),
			'Reference to inner initial handler function stored'
		);

		ret = testObj[ TEST_EVENT ](); // CALL!
		assert.equal(
			ret,
			NEH_STAGE.NATIVE,
			'calling event function returns value returned by native handler'
		);
		testNEH(
			NEH_STAGE.INITIAL + NEH_STAGE.NATIVE,
			'initial and native handlers were called (no event registered)'
		);

		// register some custom event
		$( testObj ).one( TEST_EVENT, customHandler );
		testObj[ TEST_EVENT ](); // CALL!
		testNEH(
			NEH_STAGE.INITIAL + NEH_STAGE.NATIVE + NEH_STAGE.CUSTOM,
			'initial, custom and native handlers were called'
		);

		ret = testObj[ TEST_EVENT ]( true ); // CALL!, argument triggers $.Event.cancel() test
		assert.equal(
			ret,
			NEH_STAGE.INITIAL,
			'calling event function returns value returned by initial handler because of condition in initial handler'
		);
		testNEH(
			NEH_STAGE.INITIAL,
			'only initial handler was called, which then decided to cancel the whole event'
		);

		$( testObj ).one( TEST_EVENT, function( event ) {
			customHandler();
			event.preventDefault();
			return NEH_STAGE.CUSTOM; // should be returned by event function because native handler supressed above^^
		} );
		ret = testObj[ TEST_EVENT ](); // CALL!
		assert.equal(
			ret,
			NEH_STAGE.CUSTOM,
			'calling event function returns value returned by last custom handler because default was prevented'
		);
		testNEH(
			NEH_STAGE.INITIAL + NEH_STAGE.CUSTOM,
			'only custom handlers are called after one of them requests jQuery.Event.preventDefault()'
		);

		$( testObj )
		.one( TEST_EVENT, function( event ) {
			customHandler();
			return NEH_STAGE.CUSTOM; // should be returned by event function because native handler supressed next!
		} )
		.one( TEST_EVENT, function( event ) {
			return false;
		} );
		ret = testObj[ TEST_EVENT ](); // CALL!
		assert.equal(
			ret,
			false,
			'calling event function returns value returned by first second handler even though it is false'
		);
		testNEH(
			NEH_STAGE.INITIAL + NEH_STAGE.CUSTOM,
			'only custom handlers are called after second custom handler returned false'
		);
	} );


	QUnit.test(
		'Additional jQuery.Event members used for communicating between initial handler and outer function',
		12, // make sure all tests are executed since we execute some tests from within event handlers!
	function( assert ) {

		var TEST_EVENT = 'run',
			testObj = {},
			newBasicHandlerTest = function( handlerType, numberOfAdditionalArgs ) {
				return function( event ) {
					assert.ok(
						this === testObj,
						handlerType + ' handler was called in the right context'
					);
					assert.ok(
						event instanceof $.Event,
						handlerType + ' handler callback gets jQuery.Event as first parameter'
					);
					assert.ok(
						arguments.length === numberOfAdditionalArgs + 1, // + 1 for event arg
						'all ' + numberOfAdditionalArgs + ' arguments plus one for event object get passed on'
					);
					switch( handlerType ) { // will only set a flag that the handler was called
						case 'initial': initialHandler(); break;
						case 'native': nativeHandler(); break;
						case 'custom': customHandler(); break;
					}
				};
			};

		testObj[ TEST_EVENT ] = $.NativeEventHandler( TEST_EVENT, {
			initially: function( event ) {
				newBasicHandlerTest( 'initial', 2 ).apply( this, arguments );

				assert.ok(
					event.customHandlerArgs === false,
					'jQuery.Event.customHandlerArgs is set to false'
				);

				assert.ok(
					event.nativeHandlerArgs === false,
					'jQuery.Event.customHandlerArgs is set to false'
				);

				event.customHandlerArgs = [ 1, 2, 3 ];
				event.nativeHandlerArgs = [ 1, 2, 3, 4, 5 ];
			},
			natively: newBasicHandlerTest( 'native', 5 )
		} );

		// register some custom event, execute newBasicHandlerTest tests there as well
		$( testObj ).one( TEST_EVENT, newBasicHandlerTest( 'custom', 3 ) );
		testObj[ TEST_EVENT ]( 1, 2 ); // CALL!, give two parameters for test
		testNEH(
			NEH_STAGE.INITIAL + NEH_STAGE.NATIVE + NEH_STAGE.CUSTOM,
			'initial, custom and native handlers were called'
		);
	} );


	QUnit.test( 'Excepted errors', function( assert ) {
		assert.throws(
			function() {
				$.NativeEventHandler( 'foo' );
			},
			'Can\'t create handler without function'
		);

		assert.throws(
			function() {
				$.NativeEventHandler( 'foo', {} );
			},
			'Can\'t create handler without function although 2nd parameter is set'
		);

		assert.throws(
			function() {
				$.NativeEventHandler( 'foo', { natively: $.noop, 'foo': 'test' } );
			},
			'Can\'t create handler with unknown key in 2nd parameter'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
