/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
define( [
	'valueFormatters/valueFormatters',
	'dataValues/dataValues',
	'jquery',
	'qunit',
	'formatters/NullFormatter',
	'formatters/StringFormatter',
	'valueFormatters/ValueFormatterStore',
	'values/NumberValue',
	'values/StringValue',
	'values/UnknownValue',
	'qunit.parameterize'
], function( vf, dv, $, QUnit ) {
	'use strict';

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
	 * Returns a descriptive string to be used as id when registering a ValueFormatter in a
	 * ValueFormatterStore.
	 *
	 * @param {DataTypeMock|Function} purpose
	 * @return {string}
	 */
	function getTypeInfo( purpose ) {
		if( purpose instanceof DataTypeMock ) {
			return 'DataType with data value type "' + purpose.getDataValueType() + '"';
		}
		return 'constructor for DataValue of type "' + purpose.TYPE + '"';
	}

	var StringValue = dv.StringValue,
		UnknownValue = dv.UnknownValue,
		stringType = new DataTypeMock( 'somestringtype', StringValue ),
		numberType = new DataTypeMock( 'somenumbertype', dv.NumberValue ),
		StringFormatter = vf.StringFormatter,
		NullFormatter = vf.NullFormatter;

	QUnit.module( 'valueFormatters.ValueFormatterStore' );

	QUnit.test( 'Constructor', function( assert ) {
		var formatterStore = new vf.ValueFormatterStore();

		assert.ok(
			formatterStore instanceof vf.ValueFormatterStore,
			'Instantiated ValueFormatterStore.'
		);
	} );

	QUnit.test( 'registerDataTypeFormatter(): Error handling', function( assert ) {
		var formatterStore = new vf.ValueFormatterStore();

		assert['throws'](
			function() {
				formatterStore.registerDataTypeFormatter( 'invalid', stringType.getId() );
			},
			'Failed trying to register an invalid formatter constructor.'
		);

		formatterStore.registerDataTypeFormatter( StringFormatter, stringType.getId() );

		assert['throws'](
			function() {
				formatterStore.getFormatter( stringType );
			},
			'Failed trying to get a formatter with an invalid purpose.'
		);
	} );

	QUnit.test( 'registerDataValueFormatter(): Error handling', function( assert ) {
		var formatterStore = new vf.ValueFormatterStore();

		assert['throws'](
			function() {
				formatterStore.registerDataValueFormatter( 'invalid', StringValue.TYPE );
			},
			'Failed trying to register an invalid formatter constructor.'
		);

		formatterStore.registerDataValueFormatter( StringFormatter, StringValue.TYPE );

		assert['throws'](
			function() {
				formatterStore.getFormatter( StringValue );
			},
			'Failed trying to get a formatter with an invalid purpose.'
		);
	} );

	QUnit.test( 'Return default formatter on getFormatter()', function( assert ) {
		var formatterStore = new vf.ValueFormatterStore( NullFormatter );

		assert.equal(
			formatterStore.getFormatter( StringValue.TYPE ),
			NullFormatter,
			'Returning default formatter if no formatter is registered for a specific data value.'
		);

		assert.equal(
			formatterStore.getFormatter( stringType.getDataValueType(), stringType.getId() ),
			NullFormatter,
			'Returning default formatter if no formatter is registered for a specific data type.'
		);

		formatterStore.registerDataValueFormatter( StringFormatter, StringValue.TYPE );

		assert.equal(
			formatterStore.getFormatter( StringValue.TYPE ),
			StringFormatter,
			'Returning specific formatter if a formatter is registered for a specific data value.'
		);

		assert.equal(
			formatterStore.getFormatter( UnknownValue.TYPE ),
			NullFormatter,
			'Still returning default formatter if no formatter is registered for a specific data '
				+ 'value.'
		);

		assert.equal(
			formatterStore.getFormatter( numberType.getDataValueType(), numberType.getId() ),
			NullFormatter,
			'Still returning default formatter if no formatter is registered for a specific data '
				+ 'type.'
		);
	} );

	// Tests regarding registration of formatters:

	/**
	 * Array of test definitions used as provider for "valueFormatterStoreRegistrationTest".
	 * @property {Object[]}
	 */
	var valueFormatterStoreRegistrationTestCases = [
		{
			title: 'Empty ValueFormatterStore',
			register: [],
			expect: [
				[ StringValue, null ],
				[ stringType, null ]
			]
		},
		{
			title: 'Store with formatter for string DataValue which is also suitable for string '
				+ 'DataType',
			register: [
				[ StringValue, StringFormatter ]
			],
			expect: [
				[ StringValue, StringFormatter ],
				[ stringType, StringFormatter ], // data type uses value type
				[ UnknownValue, null ],
				[ numberType, null ]
			]
		},
		{
			title: 'Store for string DataType. String DataValue can\'t use this potentially more '
				+ 'specialized formatter',
			register: [
				[ stringType, StringFormatter ]
			],
			expect: [
				[ StringValue, null ],
				[ stringType, StringFormatter ]
			]
		},
		{
			title: 'Store with two formatters: For DataValue and for DataType using that '
				+ 'DataValue type',
			register: [
				[ StringValue, StringFormatter ],
				[ stringType, StringFormatter ]
			],
			expect: [
				[ StringValue, StringFormatter ],
				[ stringType, StringFormatter ],
				[ UnknownValue, null ]
			]
		},
		{
			title: 'Store with two formatters for two different DataValue types',
			register: [
				[ StringValue, StringFormatter ],
				[ UnknownValue, NullFormatter ]
			],
			expect: [
				[ StringValue, StringFormatter ],
				[ UnknownValue, NullFormatter ],
				[ numberType, null ]
			]
		}
	];

	/**
	 * Test for registration of ValueFormatters to ValueFormatterStore and expected conditions
	 * afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays where each one tells a
	 *        ValueFormatterStore what formatters to register. The inner array has to consist out
	 *        of two objects, a formatter constructor and a DataValue constructor or a DataTypeMock
	 *        object.
	 * @param {Array[]} toExpect Array containing arrays where each one states one expected
	 *        condition of the ValueFormatterStore after registration of what is given in the first
	 *        parameter. Each inner array should contain a data type, data value or data value
	 *        constructor and a ValueFormatter which is expected to be registered for it.
	 */
	function valueFormatterStoreRegistrationTest( assert, toRegister, toExpect ) {
		var formatterStore = new vf.ValueFormatterStore();

		// Register ValueFormatters as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var purpose = registerPair[0],
				Formatter = registerPair[1];

			if( purpose instanceof DataTypeMock ) {
				formatterStore.registerDataTypeFormatter( Formatter, purpose.getId() );
			} else {
				formatterStore.registerDataValueFormatter( Formatter, purpose.TYPE );
			}

			assert.ok(
				true,
				'Registered formatter for ' + getTypeInfo( purpose )
			);
		} );

		// Check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var purpose = expectPair[0],
				Formatter = expectPair[1],
				RetrievedFormatter;

			if( purpose instanceof DataTypeMock ) {
				RetrievedFormatter = formatterStore.getFormatter(
					purpose.getDataValueType(), purpose.getId()
				);
			} else {
				RetrievedFormatter = formatterStore.getFormatter( purpose.TYPE );
			}

			assert.strictEqual(
				RetrievedFormatter,
				Formatter,
				'Requesting formatter for ' + getTypeInfo( purpose ) +
					( Formatter !== null ? ' returns expected formatter' : ' returns null' )
			);
		} );
	}

	QUnit
	.cases( valueFormatterStoreRegistrationTestCases )
		.test(
			'registerDataTypeFormatter() / registerDataValueFormatter() & getFormatter() ',
			function( params, assert ) {
				valueFormatterStoreRegistrationTest( assert, params.register, params.expect );
			}
		);

} );
