/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
 jQuery.valueview.tests = jQuery.valueview.tests || {};
 jQuery.valueview.tests.testExpert = ( function( $, QUnit, valueview, Notifier, ValueParser ) {

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

	// Used as source for expertProvider().
	var expertDefinitions = [
		{
			title: 'instance without notifier',
			args: [
				$( '<span/>' ),
				new valueview.MockViewState()
			]
		}, {
			title: 'instance with notifier',
			args: [
				$( '<div/>' ),
				new valueview.MockViewState(),
				new Notifier()
			]
		}, {
			title: 'instance with ViewState of disabled view',
			args: [
				$( '<div/>' ),
				new valueview.MockViewState( { isDisabled: true } ),
				new Notifier()
			]
		}
	];

	/**
	 * Returns an array of Objects holding the following fields:
	 * - title: Description of the set.
	 * - expert: A valueview Expert instance.
	 * - constructorArgs: Arguments used to construct the expert given in the "expert" field.
	 *
	 * @returns {Array}
	 */
	function expertProvider() {
		var experts = [];

		$.each( expertDefinitions, function( i, definition ) {
			var $viewPort = definition.args[0],
				viewState = definition.args[1],
				notifier = definition.args[2];

			var expert = new Expert( $viewPort, viewState, notifier );

			experts.push( {
				title: definition.title,
				expert: expert,
				constructorArgs: definition.args
			} );
		} );

		return experts;
	}

	var expertCases = QUnit.cases(
		// provide fresh instances for each test
		function() {
			return expertProvider();
		}
	);

	expertCases.test( 'constructor', function( args, assert ) {
		assert.ok(
			args.expert instanceof Expert,
			'expert successfully constructed'
		);
		assert.ok(
			args.expert instanceof valueview.Expert,
			'expert instance of jQuery.valueview.Expert'
		);
	} );

	expertCases.test( 'parser', function( args, assert ) {
		var valueParser = args.expert.parser();

		assert.ok(
			valueParser instanceof ValueParser,
			'parser() returns a value parser instance'
		);
		assert.ok(
			valueParser instanceof testDefinition.relatedValueParser,
			'parser() returns instance of the expected value parser constructor'
		);
	} );

	expertCases.test( 'viewState', function( args, assert ) {
		var viewState = args.expert.viewState();
		assert.ok(
			viewState instanceof valueview.ViewState,
			'viewState() returns a jQuery.valueview.ViewState instance'
		);
	} );

	expertCases.test( 'rawValueCompare: Test of different raw values', function( args, assert ) {
		$.each( validRawValues, function( i, testValue ) {
			$.each( validRawValues, function( j, otherValue ) {
				var successExpected = i === j;

				assert.ok(
					args.expert.rawValueCompare( testValue, otherValue ) === successExpected,
					'Raw value "' + valueDescription( testValue ) + ' does ' +
						( successExpected ? '' : 'not ' ) + 'equal raw value "' +
						valueDescription( otherValue )
				);
			} );
		} );
	} );

	expertCases.test( 'rawValueCompare: Works with 2nd parameter omitted', function( args, assert ) {
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

	expertCases.test( 'rawValue: initial value', function( args, assert ) {
		assert.equal(
			args.expert.rawValue(),
			null,
			'newly initialized expert has no value (rawValue() returns null)'
		);
	} );

	expertCases.test( 'rawValue: setting and getting raw value', function( args, assert ) {
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

	expertCases.test( 'rawValue: setting value to unknown value', function( args, assert ) {
		var expert = args.expert;

		$.each( unknownRawValues, function( i, testValue ) {
			expert.rawValue( testValue );

			assert.ok(
				true,
				'Changed value via "rawValue( value )". "value" is ' + valueDescription( testValue )
			);
			assert.ok(
				expert.rawValueCompare( expert.rawValue(), null ),
				'The new value returned by "rawValue()" is null (empty value)'
			);
		} );
	} );

	var expertCasesMemberCallTest = function( memberName ) {
		expertCases.test( memberName, function( args, assert ) {
			args.expert[ memberName ]();
			assert.ok(
				true,
				memberName + '() has been called'
			);
		} );
	};
	expertCasesMemberCallTest( 'draw' );
	expertCasesMemberCallTest( 'focus' );
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
			new valueview.MockViewState(),
			notifier
		);

		assert.ok( // +1
			!notified,
			'Change notification has not been triggered initially'
		);

		function testChangeRawValue( rawValue, changeExptected, testDescription ) {
			notified = false;
			newValue = rawValue;
			expert.rawValue( rawValue );

			var msg = changeExptected
				? 'Changing the expert\'s raw value has triggered a "change" notification after '
				: 'No "change" notification has been triggered since the expert\'s value did not ' +
					'change after ';
			msg += testDescription;

			assert.ok( changeExptected === notified, msg );
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
			Number['NaN'] // NaN
		]
	},
	/**
	 * Defines what kind of value parser the Expert's parse() function is exptected to return.
	 * @type Function Constructor implementation of valueParsers.ValueParser.
	 */
	relatedValueParser: null
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

}( jQuery, QUnit, jQuery.valueview, dataValues.util.Notifier, valueParsers.ValueParser ) );
