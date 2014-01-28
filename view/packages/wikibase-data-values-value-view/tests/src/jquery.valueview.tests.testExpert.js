/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
 jQuery.valueview.tests = jQuery.valueview.tests || {};
 jQuery.valueview.tests.testExpert = ( function( $, QUnit, valueview, Notifier ) {

'use strict';

 /**
  * Helper which returns a string describing some value in more detail. The string will hold a
  * quoted representation of the value and a note of what type the value is.
  *
  * @param {*} value
  * @return string
  */
function valueDescription( value ) {
	return '"' + value + '" (' + Object.prototype.toString.call( value ) + ')';
}

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

	var Expert = testDefinition.expertConstructor,
		validRawValues = testDefinition.rawValues.valid,
		validRawValue = validRawValues[0],
		unknownRawValues = testDefinition.rawValues.unknown,
		unknownRawValue = unknownRawValues[0];

	// Add null (empty) to list of valid values:
	if( $.inArray( null, validRawValues ) > 0 ) {
		throw new Error( 'null should not be part of the list of valid values since it will be ' +
			'added by default' );
	}
	validRawValues.push( null );

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

	expertCasesTestAndCleanup( 'rawValueCompare: Test of different raw values', function( args, assert ) {
		$.each( validRawValues, function( i, testValue ) {
			$.each( validRawValues, function( j, otherValue ) {
				var successExpected = i === j;

				assert.ok(
					args.expert.rawValueCompare( testValue, otherValue ) === successExpected,
					'Raw value ' + valueDescription( testValue ) + ' does ' +
						( successExpected ? '' : 'not ' ) + 'equal raw value "' +
						valueDescription( otherValue )
				);
			} );
		} );
	} );

	expertCasesTestAndCleanup( 'rawValueCompare: Works with 2nd parameter omitted', function( args, assert ) {
		var expert = args.expert;

		assert.ok(
			expert.rawValueCompare( null ),
			'"rawValueCompare( null )" is true for newly initialized expert'
		);

		expert.rawValue( validRawValue );

		assert.ok(
			expert.rawValueCompare( validRawValue ),
			'"rawValueCompare( value )" is true after "rawValue( value )"'
		);
	} );

	expertCasesTestAndCleanup( 'rawValue: initial value', function( args, assert ) {
		assert.equal(
			args.expert.rawValue(),
			null,
			'newly initialized expert has no value (rawValue() returns null)'
		);
	} );

	expertCasesTestAndCleanup( 'rawValue: setting and getting raw value', function( args, assert ) {
		var expert = args.expert;

		$.each( validRawValues, function( i, testValue ) {
			expert.rawValue( testValue );

			assert.ok(
				true,
				'Changed value via "rawValue( value )". "value" is ' + valueDescription( testValue )
			);
			assert.ok(
				expert.rawValueCompare( expert.rawValue(), testValue ),
				'The new value has been received via "rawValue()"'
			);
		} );
	} );

	expertCasesTestAndCleanup( 'rawValue: setting value to unknown value', function( args, assert ) {
		$.each( unknownRawValues, function( i, testValue ) {
			QUnit.stop();

			var expert = args.expert,
				promise = expert.rawValue( testValue );

			assert.ok(
				true,
				'Changed value via "rawValue( value )". "value" is ' + valueDescription( testValue )
			);

			if( promise && promise.state ) {
				promise.always( function() {
					QUnit.start();

					assert.ok(
						expert.rawValueCompare( expert.rawValue(), testValue )
							|| isNaN( testValue ) && isNaN( expert.rawValue() ),
						'The new value returned by "rawValue()" is ' + valueDescription( testValue )
					);
				} );

			} else {
				QUnit.start();

				assert.ok(
					expert.rawValueCompare( expert.rawValue(), null ),
					'The new value returned by "rawValue()" is null (empty value)'
				);
			}

		} );
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

	// Separate test for change notification:
	QUnit.test( 'Expert change notification', 10, function( assert ) {
		// Helper flags for tests:
		var notified = false,
			newValue;

		// Notifier with callback to set flag above after "change" notification.
		var notifier = new Notifier( {
			change: function( expert, arg2 ) {
				notified = true;
				assert.ok(
					arguments.length === 1 && expert instanceof Expert,
					'"change" notification received, first argument is expert'
				);
				assert.ok(
					expert.rawValueCompare( newValue, expert.rawValue() ),
					'Value has been changed to expected value'
				);
			}
		} );

		var expert = new Expert(
			$( '<div/>' ),
			new valueview.tests.MockViewState(),
			notifier
		);

		assert.ok( // +1
			!notified,
			'Change notification has not been triggered initially'
		);

		function testChangeRawValue( rawValue, changeExpected, testDescription ) {
			notified = false;
			newValue = rawValue;

			var msg = changeExpected
				? 'Changing the expert\'s raw value has triggered a "change" notification after '
				: 'No "change" notification has been triggered since the expert\'s value did not '
					+ 'change after ';
			msg += testDescription;

			QUnit.stop();

			var promise = expert.rawValue( rawValue );

			if( promise && promise.state ) {
				promise.always( function() {
					QUnit.start();
					assert.ok( changeExpected === notified, msg );
				} );
			} else {
				QUnit.start();
				assert.ok( changeExpected === notified, msg );
			}
		}

		testChangeRawValue( null, false, 'changing value to empty after initialization ' +
			'(value should be empty already)' ); // +1
		testChangeRawValue( validRawValue, true, 'changing value to valid value' ); // +3 assertions
		testChangeRawValue( validRawValue, false, 'attempt to change value to current value' ); // +1
		testChangeRawValue( null, true, 'change value to empty value and not' ); // +3
		testChangeRawValue( unknownRawValue, false, 'changing value to unknown value which will ' +
		'be interpreted as empty value' ); // +1
	} );
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
	expertConstructor: valueview.experts.StringValue,
	/**
	 * Definition of different raw values. Holds different fields for different kinds of raw values.
	 * The keys "valid" and "unknown" should each hold at least one value in their array of values
	 * and the values must not have any duplicates.
	 * @type Object
	 */
	rawValues: {
		/**
		 * Array of valid raw values.
		 * @type {*[]}
		 */
		valid: [],
		/**
		 * Array of unknown raw values. These values are expected to be interpreted as "null" by the
		 * Expert. null must not be part of the list.
		 */
		unknown: [
			[], // array
			{}, // plain object
			new function NotSoPlainObject() {}(), // not-so-plain object
			/regex/, // regex
			$.noop, // function
			Number.NaN // NaN
		]
	}
};

 /**
  * Will validate a test definition for the "testExpert". Throws an error if something is wrong with
  * the given test definition.
  *
  * @param {Object} testDefinition
  */
testExpert.verifyTestDefinition = function( testDefinition ) {
	function verifyTestDefinitionRawValues( rawValueDefinitions ) {
		var allValues = [],
			value, i;

		if( !$.isPlainObject( rawValueDefinitions )
			|| !rawValueDefinitions.unknown
			|| !rawValueDefinitions.valid
		) {
			throw new Error( 'Test definition\'s raw value definitions require on set of values for ' +
				'"valid" and one set of values for "unknown" values' );
		}

		$.each( rawValueDefinitions, function( valuesType, setOfValues ) {
			if( setOfValues.length < 1 ) {
				throw new Error( 'Test definition\'s set of values "' + valuesType + '" has to be an ' +
					'array of at least one value representing the type of value' );
			}
			allValues = allValues.concat( setOfValues );
		} );

		for( i = allValues.length - 1; i >= 0; i-- ) {
			value = allValues[i];
			if( value === undefined ) {
				throw new Error( 'Expert test definition\'s sets of values given in the rawValue ' +
					'field must not contain undefined as a value' );
			}
			if(
				$.inArray( value, allValues ) < i
				&& (
					typeof value !== 'number'
					|| !isNaN( value ) // NaN might be a unknown value, but NaN !== NaN, $.inArray returns -1
				)
			) {
				throw new Error( 'Expert test definition\'s sets of values given in the rawValue ' +
					'field must be sets of unique values. E.g. a unknown value can not be a valid ' +
					'value at the same time. Value ' + valueDescription( value ) + ' is a duplicate' );
			}
		}
	}

	verifyTestDefinitionRawValues( testDefinition.rawValues );

	if( !testDefinition.expertConstructor
		|| !( testDefinition.expertConstructor.prototype instanceof valueview.Expert )
	) {
		throw new Error( 'Test definition\'s "expertConstructor" field has to hold a constructor ' +
			'implementing jQuery.valueview.Expert' );
	}
};

return testExpert; // expose

}( jQuery, QUnit, jQuery.valueview, util.Notifier ) );
