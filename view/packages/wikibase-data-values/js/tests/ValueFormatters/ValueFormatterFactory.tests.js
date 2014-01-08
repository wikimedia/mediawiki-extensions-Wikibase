/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, dt, dv, vf ) {
	'use strict';

	/**
	 * Returns a descriptive string to be used as id when registering a ValueFormatter in a
	 * ValueFormatterFactory.
	 *
	 * @param {dataTypes.DataType|string} purpose
	 * @return {string}
	 */
	function getTypeInfo( purpose ) {
		if( purpose instanceof dt.DataType ) {
			return 'DataType with data value type "' + purpose.getDataValueType() + '"';
		}
		return 'constructor for DataValue of type "' + purpose + '"';
	}

	var StringValue = dv.StringValue,
		UnknownValue = dv.UnknownValue,
		stringType = new dt.DataType( 'somestringtype', StringValue ),
		numberType = new dt.DataType( 'somenumbertype', dv.NumberValue ),
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

	QUnit.test( 'Error handling', function( assert ) {
		var formatterFactory = new vf.ValueFormatterFactory();

		assert.throws(
			function() {
				formatterFactory.registerFormatter( StringValue.TYPE, 'invalid' );
			},
			'Failed trying to register an invalid formatter constructor.'
		);

		assert.throws(
			function() {
				formatterFactory.register( 'invalid', StringFormatter );
			},
			'Failed trying to register a formatter with an invalid purpose.'
		);

		formatterFactory.registerFormatter( StringValue.TYPE, StringFormatter );

		assert.throws(
			function() {
				formatterFactory.getFormatter( StringValue );
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

		formatterFactory.registerFormatter( StringValue.TYPE, StringFormatter );

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
				[ StringValue.TYPE, null ],
				[ stringType, null ]
			]
		},
		{
			title: 'Factory with formatter for string DataValue which is also suitable for string '
				+ 'DataType',
			register: [
				[ StringValue.TYPE, StringFormatter ]
			],
			expect: [
				[ StringValue.TYPE, StringFormatter ],
				[ stringType, StringFormatter ], // data type uses value type
				[ UnknownValue.TYPE, null ],
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
				[ StringValue.TYPE, null ],
				[ stringType, StringFormatter ]
			]
		},
		{
			title: 'Factory with two formatters: For DataValue and for DataType using that '
				+ 'DataValue type',
			register: [
				[ StringValue.TYPE, StringFormatter ],
				[ stringType, StringFormatter ]
			],
			expect: [
				[ StringValue.TYPE, StringFormatter ],
				[ stringType, StringFormatter ],
				[ UnknownValue.TYPE, null ]
			]
		},
		{
			title: 'Factory with two formatters for two different DataValue types',
			register: [
				[ StringValue.TYPE, StringFormatter ],
				[ UnknownValue.TYPE, NullFormatter ]
			],
			expect: [
				[ StringValue.TYPE, StringFormatter ],
				[ UnknownValue.TYPE, NullFormatter ],
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
	 *        of two objects as ValueFormatterFactory.registerFormatter would take them.
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
				formatter = registerPair[1];

			formatterFactory.registerFormatter( purpose, formatter );

			assert.ok(
				true,
				'Registered formatter for ' + getTypeInfo( purpose )
			);
		} );

		// Check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var purpose = expectPair[0],
				formatter = expectPair[1];

			assert.strictEqual(
				formatterFactory.getFormatter( purpose ),
				formatter,
				'Requesting formatter for ' + getTypeInfo( purpose ) +
					( formatter !== null ? ' returns expected formatter' : ' returns null' )
			);
		} );
	}

	QUnit
	.cases( valueFormatterFactoryRegistrationTestCases )
		.test( 'registerFormatter() & getFormatter() ', function( params, assert ) {
			valueFormatterFactoryRegistrationTest( assert, params.register, params.expect );
		} );

}( jQuery, QUnit, dataTypes, dataValues, valueFormatters ) );
