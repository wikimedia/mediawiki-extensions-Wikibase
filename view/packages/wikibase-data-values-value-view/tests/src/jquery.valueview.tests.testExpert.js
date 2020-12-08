/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
jQuery.valueview.tests = jQuery.valueview.tests || {};
jQuery.valueview.tests.testExpert = ( function( $, QUnit, valueview, Notifier ) {

'use strict';

 /**
  * Tests different aspects of a valueview expert.
  *
  * @since 0.1
  *
  * TODO: Test error cases.
  *
  * @param {Object} testDefinition See testExpert.basicTestDefinition for documentation.
  */
function testExpert( testDefinition ) {
	// Throw error if something is wrong with given test definition:
	testExpert.verifyTestDefinition( testDefinition );

	var Expert = testDefinition.expertConstructor;

	function createExpertDefinitions() {
		return [
			{
				title: 'instance without notifier',
				constructorArgs: [
					$( '<span/>' ),
					new valueview.tests.MockViewState()
				]
			}, {
				title: 'instance with notifier',
				constructorArgs: [
					document.createElement( 'div' ),
					new valueview.tests.MockViewState(),
					new Notifier()
				]
			}, {
				title: 'instance with ViewState of disabled view',
				constructorArgs: [
					$( '<div/>' ),
					new valueview.tests.MockViewState( { isDisabled: true } ),
					new Notifier()
				]
			}
		];
	}

	// We always have to destroy experts so all widgets used by them get destroyed as well in case
	// they add something to the body.
	function expertCasesTestAndCleanup( description, testFn ) {
		createExpertDefinitions().forEach( function ( definition ) {
			QUnit.test( description + ' (' + definition.title + ')', function( assert ) {
				var $viewPort = definition.constructorArgs[0],
					viewState = definition.constructorArgs[1],
					notifier = definition.constructorArgs[2];

				definition.expert = new Expert( $viewPort, viewState, notifier, { messages: {} } );
				definition.expert.init();
				testFn( definition, assert );
				definition.expert.destroy();
			} );
		} );
	}

	expertCasesTestAndCleanup( 'constructor', function( args, assert ) {
		assert.ok(
			args.expert instanceof Expert,
			'expert successfully constructed'
		);
		assert.ok(
			args.expert instanceof valueview.Expert,
			'expert instance of jQuery.valueview.Expert'
		);

		var viewPortArg = args.constructorArgs[0],
			viewPortElement = viewPortArg instanceof $ ? viewPortArg.get( 0 ) : viewPortArg;

		assert.ok(
			args.expert.$viewPort.get( 0 ) === viewPortElement,
			'View port node given to constructor is used as the actual view port of the expert'
		);
	} );

	expertCasesTestAndCleanup( 'destroy', function( args, assert ) {
		var $viewPort = $( args.constructorArgs[0] );

		args.expert.destroy();

		assert.ok(
			$viewPort.children().length === 0 && $viewPort.text() === '',
			'Viewport is empty after expert\'s destruction'
		);

		args.expert.destroy();
		assert.ok( true, 'Calling destroy() again will not lead to unexpected error.' );

	} );

	expertCasesTestAndCleanup( 'valueCharacteristics', function( args, assert ) {
		var valueCharacteristics = args.expert.valueCharacteristics();

		assert.ok(
			$.isPlainObject( valueCharacteristics ),
			'valueCharacteristics() returns a plain object'
		);
	} );

	expertCasesTestAndCleanup( 'viewState', function( args, assert ) {
		var viewState = args.expert.viewState();
		assert.notEqual(
			viewState.getFormattedValue, 'undefined',
			'viewState() returns a jQuery.valueview.ViewState instance'
		);
	} );

	expertCasesTestAndCleanup( 'rawValue: initial value', function( args, assert ) {
		var rawValue = args.expert.rawValue();
		assert.ok(
			rawValue === '' || rawValue === null,
			'newly initialized expert has no value (rawValue() returns empty string or null)'
		);
	} );

	var expertCasesMemberCallTest = function( memberName, additionalAssertionsFn ) {
		expertCasesTestAndCleanup( memberName, function( args, assert ) {
			args.expert[ memberName ]();
			assert.ok(
				true,
				memberName + '() has been called'
			);
			if ( additionalAssertionsFn ) {
				additionalAssertionsFn( args, assert );
			}
		} );
	};
	expertCasesMemberCallTest( 'draw', function( args, assert ) {
		var $viewPort = $( args.constructorArgs[0] );

		assert.ok(
			$viewPort.text() !== '' || $viewPort.children.length > 0,
			'Viewport node is not empty after draw()'
		);
	} );

	expertCasesTestAndCleanup( 'focus', function( args, assert ) {
		try {
			args.expert.focus();
		} catch ( e ) {
			assert.ok(
				e.name === 'NS_ERROR_FAILURE' && e.result === 0x80004005,
				'Unable to focus since browser requires element to be in the DOM.'
			);
			return;
		}
		assert.ok(
			true,
			'focus() has been called.'
		);
	} );

	expertCasesMemberCallTest( 'blur' );
}

 /**
  * Object holding all fields required by testExpert's first argument's object.
  *
  * @since 0.1
  * @property {Object}
  */
testExpert.basicTestDefinition = {
	/**
	 * A jQuery.valueview.Expert implementation's constructor to be tested.
	 *
	 * @property {Function}
	 */
	expertConstructor: valueview.experts.StringValue
};

 /**
  * Will validate a test definition for the "testExpert". Throws an error if something is wrong with
  * the given test definition.
  *
  * @param {Object} testDefinition
  *
  * @throws {Error} if testDefinitions expertConstructor field is not an implementation of
  *         jQuery.valueview.Expert.
  */
testExpert.verifyTestDefinition = function( testDefinition ) {
	if ( !testDefinition.expertConstructor
		|| !( testDefinition.expertConstructor.prototype instanceof valueview.Expert )
	) {
		throw new Error( 'Test definition\'s "expertConstructor" field has to hold a constructor '
			+ 'implementing jQuery.valueview.Expert' );
	}
};

return testExpert; // expose

}( jQuery, QUnit, jQuery.valueview, util.Notifier ) );
