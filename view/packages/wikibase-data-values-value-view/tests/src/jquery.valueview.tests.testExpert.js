/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
 jQuery.valueview.tests = jQuery.valueview.tests || {};
 jQuery.valueview.tests.testExpert = ( function( $, QUnit, valueview, Notifier ) {

'use strict';

 /**
  * Tests different aspects of a valueview expert.
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

	// Used as source for expertProviders.
	function createExpertDefinitions() {
		return [
			{
				title: 'instance without notifier',
				args: [
					$( '<span/>' ),
					new valueview.tests.MockViewState()
				]
			}, {
				title: 'instance with notifier',
				args: [
					document.createElement( 'div' ),
					new valueview.tests.MockViewState(),
					new Notifier()
				]
			}, {
				title: 'instance with ViewState of disabled view',
				args: [
					$( '<div/>' ),
					new valueview.tests.MockViewState( { isDisabled: true } ),
					new Notifier()
				]
			}
		];
	}

	/**
	 * Returns an array of Functions. Each function returns an object with the following fields
	 * when executed:
	 * - expert: A valueview Expert instance.
	 * - constructorArgs: Arguments used to construct the expert given in the "expert" field.
	 *
	 * Each function has a "title" field which describes the expert instance mentioned above.
	 *
	 * @return {Function[]}
	 */
	function createExpertsProvider() {
		return $.map( createExpertDefinitions(), function( definition ) {
			// Provide a setup function for test case parameter creation instead of creating a case
			// definition object directly. If that would be done later, the expert would already
			// be created and, in some cases, create conflicts with other tests since some experts
			// immediately instantiate certain widgets (e.g. inputextender).
			var caseSetup = function() {
				var $viewPort = definition.args[0],
					viewState = definition.args[1],
					notifier = definition.args[2];

				var expert = new Expert( $viewPort, viewState, notifier );

				return {
					expert: expert,
					constructorArgs: definition.args
				};
			};
			caseSetup.title = definition.title;
			return caseSetup;
		} );
	}

	var expertCases = QUnit.cases( createExpertsProvider );

	// We always have to destroy experts so all widgets used by them get destroyed as well in case
	// they add something to the body.
	function expertCasesTestAndCleanup( description, testFn ) {
		expertCases.test( description, function( args, assert ) {
			testFn( args, assert );
			args.expert.destroy();
		} );
	}

	QUnit.test( 'valueCharacteristics static invocation', function( assert ) {
		assert.equal(
			typeof Expert.prototype.valueCharacteristics(), 'object',
			'valueCharacteristics returns an object if called statically' );
	} );

	expertCasesTestAndCleanup( 'valueCharacteristics non-static invocation', function( args, assert ) {
		assert.equal(
			typeof args.expert.valueCharacteristics(), 'object',
			'valueCharacteristics returns an object if called on an instance' );
	} );

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
		assert.ok(
			viewState instanceof valueview.ViewState,
			'viewState() returns a jQuery.valueview.ViewState instance'
		);
	} );

	expertCasesTestAndCleanup( 'rawValue: initial value', function( args, assert ) {
		assert.equal(
			args.expert.rawValue(),
			'',
			'newly initialized expert has no value (rawValue() returns empty string)'
		);
	} );

	var expertCasesMemberCallTest = function( memberName, additionalAssertionsFn ) {
		expertCasesTestAndCleanup( memberName, function( args, assert ) {
			args.expert[ memberName ]();
			assert.ok(
				true,
				memberName + '() has been called'
			);
			if( additionalAssertionsFn ) {
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
		} catch( e ) {
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
  * @since 0.1
  * @type Object
  */
testExpert.basicTestDefinition = {
	/**
	 * A jQuery.valueview.Expert implementation's constructor to be tested.
	 * @type Function
	 */
	expertConstructor: valueview.experts.StringValue
};

 /**
  * Will validate a test definition for the "testExpert". Throws an error if something is wrong with
  * the given test definition.
  *
  * @param {Object} testDefinition
  */
testExpert.verifyTestDefinition = function( testDefinition ) {
	if( !testDefinition.expertConstructor
		|| !( testDefinition.expertConstructor.prototype instanceof valueview.Expert )
	) {
		throw new Error( 'Test definition\'s "expertConstructor" field has to hold a constructor ' +
			'implementing jQuery.valueview.Expert' );
	}
};

return testExpert; // expose

}( jQuery, QUnit, jQuery.valueview, util.Notifier ) );
