/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, dv, vf ) {
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
	 * ValueFormatterFactory.
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

	QUnit.module( 'valueFormatters.ValueFormatterFactory' );

	QUnit.test( 'Constructor', function( assert ) {
		var formatterFactory = new vf.ValueFormatterFactory();

		assert.ok(
			formatterFactory instanceof vf.ValueFormatterFactory,
			'Instantiated ValueFormatterFactory.'
		);
	} );

	QUnit.test( 'registerDataTypeFormatter(): Error handling', function( assert ) {
		var formatterFactory = new vf.ValueFormatterFactory();

		assert.throws(
			function() {
				formatterFactory.registerDataTypeFormatter( 'invalid', stringType.getId() );
			},
			'Failed trying to register an invalid formatter constructor.'
		);

		assert.throws(
			function() {
				formatterFactory.registerDataTypeFormatter( StringFormatter, 'invalid' );
			},
			'Failed trying to register a formatter with an invalid purpose.'
		);

		formatterFactory.registerDataTypeFormatter( StringFormatter, stringType.getId() );

		assert.throws(
			function() {
				formatterFactory.getFormatter( stringType.getId() );
			},
			'Failed trying to get a formatter with an invalid purpose.'
		);
	} );

	QUnit.test( 'registerDataValueFormatter(): Error handling', function( assert ) {
		var formatterFactory = new vf.ValueFormatterFactory();

		assert.throws(
			function() {
				formatterFactory.registerDataValueFormatter( 'invalid', StringValue.TYPE );
			},
			'Failed trying to register an invalid formatter constructor.'
		);

		assert.throws(
			function() {
				formatterFactory.registerDataValueFormatter( StringFormatter, 'invalid' );
			},
			'Failed trying to register a formatter with an invalid purpose.'
		);

		formatterFactory.registerDataValueFormatter( StringFormatter, StringValue.TYPE );

		assert.throws(
			function() {
				formatterFactory.getFormatter( StringValue.TYPE );
			},
			'Failed trying to get a formatter with an invalid purpose.'
		);
	} );

	QUnit.test( 'Return default formatter on getFormatter()', function( assert ) {
		var formatterFactory = new vf.ValueFormatterFactory( NullFormatter );

		assert.equal(
			formatterFactory.getFormatter( StringValue.TYPE ),
			NullFormatter,
			'Returning default formatter if no formatter is registered for a specific data value.'
		);

		assert.equal(
			formatterFactory.getFormatter( stringType.getDataValueType(), stringType.getId() ),
			NullFormatter,
			'Returning default formatter if no formatter is registered for a specific data type.'
		);

		formatterFactory.registerDataValueFormatter( StringFormatter, StringValue.TYPE );

		assert.equal(
			formatterFactory.getFormatter( StringValue.TYPE ),
			StringFormatter,
			'Returning specific formatter if a formatter is registered for a specific data value.'
		);

		assert.equal(
			formatterFactory.getFormatter( UnknownValue.TYPE ),
			NullFormatter,
			'Still returning default formatter if no formatter is registered for a specific data '
				+ 'value.'
		);

		assert.equal(
			formatterFactory.getFormatter( numberType.getDataValueType(), numberType.getId()  ),
			NullFormatter,
			'Still returning default formatter if no formatter is registered for a specific data '
				+ 'type.'
		);
	} );

	// Tests regarding registration of formatters:

	/**
	 * Array of test definitions used as provider for "valueFormatterFactoryRegistrationTest".
	 * @type {Object[]}
	 */
	var valueFormatterFactoryRegistrationTestCases = [
		{
			title: 'Empty ValueFormatterFactory',
			register: [],
			expect: [
				[ StringValue, null ],
				[ stringType, null ]
			]
		},
		{
			title: 'Factory with formatter for string DataValue which is also suitable for string '
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
			title: 'Factory for string DataType. String DataValue can\'t use this potentially more '
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
			title: 'Factory with two formatters: For DataValue and for DataType using that '
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
			title: 'Factory with two formatters for two different DataValue types',
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
	 * Test for registration of ValueFormatters to ValueFormatterFactory and expected conditions
	 * afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays where each one tells a
	 *        ValueFormatterFactory what formatters to register. The inner array has to consist out
	 *        of two objects, a formatter constructor and a DataValue constructor or a DataTypeMock
	 *        object.
	 * @param {Array[]} toExpect Array containing arrays where each one states one expected
	 *        condition of the ValueFormatterFactory after registration of what is given in the
	 *        first parameter. Each inner array should contain a data type, data value or data value
	 *        constructor and a ValueFormatter which is expected to be registered for it.
	 */
	function valueFormatterFactoryRegistrationTest( assert, toRegister, toExpect  ) {
		var formatterFactory = new vf.ValueFormatterFactory();

		// Register ValueFormatters as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var purpose = registerPair[0],
				Formatter = registerPair[1];

			if( purpose instanceof DataTypeMock ) {
				formatterFactory.registerDataTypeFormatter( Formatter, purpose.getId() );
			} else {
				formatterFactory.registerDataValueFormatter( Formatter, purpose.TYPE );
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
				RetrievedFormatter = formatterFactory.getFormatter(
					purpose.getDataValueType(), purpose.getId()
				);
			} else {
				RetrievedFormatter = formatterFactory.getFormatter( purpose.TYPE );
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
	.cases( valueFormatterFactoryRegistrationTestCases )
		.test(
			'registerDataTypeFormatter() / registerDataValueFormatter() & getFormatter() ',
			function( params, assert ) {
				valueFormatterFactoryRegistrationTest( assert, params.register, params.expect );
			}
		);

}( jQuery, QUnit, dataValues, valueFormatters ) );
