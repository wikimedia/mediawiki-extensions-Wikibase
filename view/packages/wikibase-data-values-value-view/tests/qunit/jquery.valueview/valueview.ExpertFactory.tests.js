/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( $, QUnit, dataValues, dataTypes ) {
	'use strict';

	var dv = dataValues,
		dt = dataTypes,
		vv = $.valueview,
		ExpertFactory = vv.ExpertFactory,
		MockExpertBase = vv.experts.Mock;

	/**
	 * Returns a descriptive string about a valid expert base object (a DataType object, a
	 * DataValue object or a DataValue constructor).
	 *
	 * @param {dataTypes.DataType|dataValues.DataValue|Function} expertBasis
	 * @returns {string}
	 */
	function expertBasisInfo( expertBasis ) {
		if( expertBasis instanceof dt.DataType ) {
			return 'DataType with data value type "' + expertBasis.getDataValueType() + '"';
		}
		if( expertBasis instanceof dv.DataValue ) {
			return 'DataValue instance of type "' + expertBasis.getType() + '"';
		}
		// DataValue constructor:
		return 'constructor for DataValue of type "' + expertBasis.TYPE + '"';
	}

	/**
	 * Creates a new valueview expert constructor.
	 *
	 * @param {string} mockExpertId Used in the constructor name for simple identification if some
	 *        assertion goes wrong.
	 * @returns {jQuery.valueview.Expert}
	 */
	function newMockExpertConstructor( mockExpertId ) {
		return vv.expert(
			'mockexpert' + mockExpertId, // name
			MockExpertBase, // base
			{} // definition
		);
	}

	var StringValue = dv.StringValue,
		BoolValue = dv.BoolValue,
		stringDataType = new dt.DataType( 'somestringtype', StringValue ),
		numberDataType = new dt.DataType( 'somenumbertype', dv.NumberValue ),
		MockExpertForStringValue = newMockExpertConstructor( 'ForStringValue' ),
		MockExpertForBoolValue = newMockExpertConstructor( 'ForBoolValue' ),
		MockExpertForStringDataType = newMockExpertConstructor( 'ForStringDataType' );

	QUnit.module( 'jquery.valueview.ExpertFactory' );

	QUnit.test( 'constructor', function( assert ) {
		var expertFactory = new ExpertFactory();

		assert.ok(
			expertFactory instanceof ExpertFactory,
			'New ExpertFactory instance created'
		);

		assert.ok(
			expertFactory.getCoveredDataValueTypes().length === 0,
			'Initially, new ExpertFactory has registered no experts for any data value type'
		);

		assert.ok(
			expertFactory.getCoveredDataTypes().length === 0,
			'Initially, new ExpertFactory has registered no experts for any data type'
		);
	} );

	QUnit.test( 'test getCoveredDataValueTypes', function( assert ) {
		var expertFactory = new ExpertFactory();

		// Register two experts for data values:
		expertFactory.registerExpert( StringValue, MockExpertForStringValue );
		expertFactory.registerExpert( BoolValue, MockExpertForBoolValue );

		// Register one data type expert, shouldn't make any difference:
		expertFactory.registerExpert( stringDataType, MockExpertForBoolValue );

		assert.ok(
			expertFactory.hasExpertFor( BoolValue ),
			'Expert registered for another data value type'
		);

		var coveredDvTypes = expertFactory.getCoveredDataValueTypes();

		assert.equal(
			coveredDvTypes.length,
			2,
			'There are experts registered for exactly two data value types'
		);

		assert.ok(
			$.inArray( StringValue.TYPE, coveredDvTypes ) !== -1
				&& $.inArray( BoolValue.TYPE, coveredDvTypes ) !== -1,
			'Both registered data value types are returned by getCoveredDataValueTypes()'
		);
	} );

	// tests for registration of experts:

	/**
	 * Array of test definitions as provider for "expertFactoryRegistrationTest" plus one "descr"
	 * field for each test.
	 *
	 * @type {Object[]}
	 */
	var expertFactoryRegistrationTestCases = [
		{
			title: 'empty ExpertFactory',
			register: [],
			expect: [
				[ StringValue, null ],
				[ stringDataType, null ]
			]
		}, {
			title: 'ExpertFactory with expert for string value, expert also suitable for string type',
			register: [
				[ StringValue, MockExpertForStringValue ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ new StringValue( 'foo' ), MockExpertForStringValue ],
				[ stringDataType, MockExpertForStringValue ], // data type uses value type
				[ BoolValue, null ],
				[ new BoolValue( true ), null ],
				[ numberDataType, null ]
			]
		}, {
			title: 'ExpertFactory for string data type. String value can\'t use this potentially more specialized expert',
			register: [
				[ stringDataType, MockExpertForStringDataType ]
			],
			expect: [
				[ StringValue, null ],
				[ new StringValue( 'foo' ), null ],
				[ stringDataType, MockExpertForStringDataType ]
			]
		}, {
			title: 'ExpertFactory with two experts: For data value and for data type using that data value type',
			register: [
				[ StringValue, MockExpertForStringValue ],
				[ stringDataType, MockExpertForStringDataType ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ stringDataType, MockExpertForStringDataType ],
				[ BoolValue, null ]
			]
		}, {
			title: 'ExpertFactory with two experts for two different data value types',
			register: [
				[ StringValue, MockExpertForStringValue ],
				[ BoolValue, MockExpertForBoolValue ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ BoolValue, MockExpertForBoolValue ],
				[ numberDataType, null ]
			]
		}
	];

	/**
	 * Test for registration of experts to ExpertFactory and expected conditions afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays where each one tells a ExpertFactory what
	 *        experts to register. The inner array has to consist out of two objects as
	 *        ExpertFactory.registerExpert would take them.
	 * @param {Array[]} toExpect Array containing arrays where each one states one expected
	 *        condition of the ExpertFactory after registration of what is given in the first
	 *        parameter. Each inner array should contain a data type, data value or data value
	 *        constructor and an expert which is expected to be registered for it.
	 */
	function expertFactoryRegistrationTest( assert, toRegister, toExpect  ) {
		var expertFactory = new ExpertFactory();

		assert.ok(
			expertFactory instanceof ExpertFactory,
			'New expert factory created'
		);

		// register experts as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var expertBasis = registerPair[0],
				expert = registerPair[1];

			expertFactory.registerExpert( expertBasis, expert );

			assert.ok(
				true,
				'Expert registered for ' + expertBasisInfo( expertBasis )
			);
		} );

		// check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var expertBasis = expectPair[0],
				expectedExpert = expectPair[1],
				expertBasisInfoMsg = expertBasisInfo( expertBasis );

			assert.strictEqual(
				expertFactory.hasExpertFor( expertBasis ),
				expectedExpert !== null,
				'Expert factory has ' + ( expectedExpert !== null ? '' : ' no' )
					+ ' expert for' + expertBasisInfoMsg
			);

			assert.strictEqual(
				expertFactory.getExpert( expertBasis ),
				expectedExpert,
				'Requesting expert for ' + expertBasisInfoMsg +
					( expectedExpert !== null ? ' returns expected expert' : ' returns null' )
			);
		} );
	}

	// Run defined tests on expertFactoryRegistrationTest:
	QUnit
	.cases( expertFactoryRegistrationTestCases )
		.test( 'experts registration', function( params, assert ) {
			expertFactoryRegistrationTest( assert, params.register, params.expect );
		} );

}( jQuery, QUnit, dataValues, dataTypes ) );
