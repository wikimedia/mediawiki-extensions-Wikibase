/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, dv, QUnit ) {
	'use strict';

	var vv = $.valueview;

	var DataTypeMock = function( dataTypeId, DataValue ) {
		this._dataTypeId = dataTypeId;
		this._dataValueType = DataValue.TYPE;
	};
	$.extend( DataTypeMock.prototype, {
		getId: function() {
			return this._dataTypeId;
		},
		getDataValueType: function() {
			return this._dataValueType;
		}
	} );

	/**
	 * Returns a descriptive string to be used as id when registering an expert in an ExpertStore.
	 *
	 * @param {DataTypeMock|Function} purpose
	 * @return {string}
	 */
	function getTypeInfo( purpose ) {
		if ( purpose instanceof DataTypeMock ) {
			return 'DataType with data value type "' + purpose.getDataValueType() + '"';
		}
		return 'constructor for DataValue of type "' + purpose.TYPE + '"';
	}

	/**
	 * Creates a new valueview expert constructor.
	 *
	 * @param {string} mockExpertId Used in the constructor name for simple identification if some
	 *        assertion goes wrong.
	 * @return {jQuery.valueview.Expert}
	 */
	function newMockExpertConstructor( mockExpertId ) {
		return vv.expert(
			'mockexpert' + mockExpertId, // name
			vv.tests.MockExpert, // base
			{} // definition
		);
	}

	var StringValue = dv.StringValue,
		UnknownValue = dv.UnknownValue,
		stringType = new DataTypeMock( 'somestringtype', StringValue ),
		numberType = new DataTypeMock( 'somenumbertype', dv.NumberValue ),
		MockExpertForStringValue = newMockExpertConstructor( 'ForStringValue' ),
		MockExpertForStringDataType = newMockExpertConstructor( 'ForStringDataType' ),
		MockExpertForUnsupportedValue = newMockExpertConstructor( 'ForUnsupportedValue' );

	QUnit.module( 'jquery.valueview.ExpertStore' );

	QUnit.test( 'Constructor', function( assert ) {
		var expertStore = new vv.ExpertStore();

		assert.ok(
			expertStore instanceof vv.ExpertStore,
			'Instantiated ExpertStore.'
		);
	} );

	QUnit.test( 'registerDataTypeExpert(): Error handling', function( assert ) {
		var expertStore = new vv.ExpertStore();

		assert.throws(
			function() {
				expertStore.registerDataTypeExpert( 'invalid', stringType.getId() );
			},
			'Failed trying to register an invalid expert constructor.'
		);

		expertStore.registerDataTypeExpert( MockExpertForStringDataType, stringType.getId() );

		assert.throws(
			function() {
				expertStore.getExpert( stringType );
			},
			'Failed trying to get an expert with an invalid purpose.'
		);
	} );

	QUnit.test( 'registerDataValueExpert(): Error handling', function( assert ) {
		var expertStore = new vv.ExpertStore();

		assert.throws(
			function() {
				expertStore.registerDataValueExpert( 'invalid', StringValue.TYPE );
			},
			'Failed trying to register an invalid expert constructor.'
		);

		expertStore.registerDataValueExpert( MockExpertForStringValue, StringValue.TYPE );

		assert.throws(
			function() {
				expertStore.getExpert( StringValue );
			},
			'Failed trying to get an expert with an invalid purpose.'
		);
	} );

	QUnit.test( 'Return default expert constructor on getExpert()', function( assert ) {
		var expertStore = new vv.ExpertStore( MockExpertForUnsupportedValue );

		assert.strictEqual(
			expertStore.getExpert( StringValue.TYPE ),
			MockExpertForUnsupportedValue,
			'Returning default expert if no expert is registered for a specific data value.'
		);

		assert.strictEqual(
			expertStore.getExpert( stringType.getDataValueType(), stringType.getId() ),
			MockExpertForUnsupportedValue,
			'Returning default if no expert is registered for a specific data type.'
		);

		expertStore.registerDataValueExpert( MockExpertForStringValue, StringValue.TYPE );

		assert.strictEqual(
			expertStore.getExpert( StringValue.TYPE ),
			MockExpertForStringValue,
			'Returning specific expert if an expert is registered for a specific data value.'
		);

		assert.strictEqual(
			expertStore.getExpert( UnknownValue.TYPE ),
			MockExpertForUnsupportedValue,
			'Still returning default expert if no expert is registered for a specific data value.'
		);

		assert.strictEqual(
			expertStore.getExpert( numberType.getDataValueType(), numberType.getId() ),
			MockExpertForUnsupportedValue,
			'Still returning default expert if no expert is registered for a specific data type.'
		);
	} );

	// Tests for registration of experts:

	/**
	 * Array of test definitions as provider for "expertStoreRegistrationTest".
	 *
	 * @property {Object[]}
	 */
	var expertStoreRegistrationTestCases = [
		{
			title: 'Empty store',
			register: [],
			expect: [
				[ StringValue, null ],
				[ stringType, null ]
			]
		}, {
			title: 'Store with expert for string DataValue which is also suitable for string '
				+ 'DataType',
			register: [
				[ StringValue, MockExpertForStringValue ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ stringType, MockExpertForStringValue ], // data type uses value type
				[ UnknownValue, null ],
				[ numberType, null ]
			]
		}, {
			title: 'Store for string DataType. String value can\'t use this potentially more '
				+ 'specialized expert',
			register: [
				[ stringType, MockExpertForStringDataType ]
			],
			expect: [
				[ StringValue, null ],
				[ stringType, MockExpertForStringDataType ]
			]
		}, {
			title: 'Store with two experts: For DataValue and for DataType using that DataValue '
				+ 'type',
			register: [
				[ StringValue, MockExpertForStringValue ],
				[ stringType, MockExpertForStringDataType ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ stringType, MockExpertForStringDataType ],
				[ UnknownValue, null ]
			]
		}, {
			title: 'Store with two experts for two different DataValue types',
			register: [
				[ StringValue, MockExpertForStringValue ],
				[ UnknownValue, MockExpertForUnsupportedValue ]
			],
			expect: [
				[ StringValue, MockExpertForStringValue ],
				[ UnknownValue, MockExpertForUnsupportedValue ],
				[ numberType, null ]
			]
		}
	];

	/**
	 * Test for registration of experts to ExpertStore and expected conditions afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays each telling an ExpertStore what
	 *        experts to register. The inner array has to consist out of two objects, an Expert
	 *        constructor and a DataValue constructor or a DataTypeMock object.
	 * @param {Array[]} toExpect Array containing arrays each one stating one expected condition
	 *        of the ExpertStore after registration of what is given in the first
	 *        parameter. Each inner array should contain a DataTypeMock object or a DataValue
	 *        constructor and an Expert constructor which is expected to be registered for it.
	 */
	function expertStoreRegistrationTest( assert, toRegister, toExpect ) {
		var expertStore = new vv.ExpertStore();

		// Register experts as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var purpose = registerPair[0],
				Expert = registerPair[1];

			if ( purpose instanceof DataTypeMock ) {
				expertStore.registerDataTypeExpert( Expert, purpose.getId() );
			} else {
				expertStore.registerDataValueExpert( Expert, purpose.TYPE );
			}

			assert.ok(
				true,
				'Registered expert for ' + getTypeInfo( purpose )
			);
		} );

		// Check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var purpose = expectPair[0],
				Expert = expectPair[1],
				RetrievedExpert;

			if ( purpose instanceof DataTypeMock ) {
				RetrievedExpert = expertStore.getExpert(
					purpose.getDataValueType(), purpose.getId()
				);
			} else {
				RetrievedExpert = expertStore.getExpert( purpose.TYPE );
			}

			assert.strictEqual(
				RetrievedExpert,
				Expert,
				'Requesting expert for ' + getTypeInfo( purpose ) +
					( Expert !== null ? ' returns expected expert' : ' returns null' )
			);
		} );
	}

	expertStoreRegistrationTestCases.forEach( function ( params ) {
		QUnit.test(
			'registerDataTypeExpert()/registerDataValueExpert() & getExpert()',
			function( assert ) {
				expertStoreRegistrationTest( assert, params.register, params.expect );
			}
		);
	} );

}( jQuery, dataValues, QUnit ) );
