/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit, dt, dv, vp ) {
	'use strict';

	/**
	 * Returns a descriptive string to be used as id when registering a ValueParser in a
	 * ValueParserFactory.
	 *
	 * @param {dataTypes.DataType|dataValues.DataValue|Function} purpose
	 * @return {string}
	 */
	function getTypeInfo( purpose ) {
		if( purpose instanceof dt.DataType ) {
			return 'DataType with data value type "' + purpose.getDataValueType() + '"';
		}
		if( purpose instanceof dv.DataValue ) {
			return 'DataValue instance of type "' + purpose.getType() + '"';
		}
		// DataValue constructor:
		return 'constructor for DataValue of type "' + purpose.TYPE + '"';
	}

	var StringValue = dv.StringValue,
		BoolValue = dv.BoolValue,
		stringType = new dt.DataType( 'somestringtype', StringValue ),
		numberType = new dt.DataType( 'somenumbertype', dv.NumberValue ),
		StringParser = vp.StringParser,
		BoolParser = vp.BoolParser;

	QUnit.module( 'valueParsers.ValueParserFactory' );

	QUnit.test( 'Constructor', function( assert ) {
		var parserFactory = new vp.ValueParserFactory();

		assert.ok(
			parserFactory instanceof vp.ValueParserFactory,
			'Instantiated ValueParserFactory.'
		);
	} );

	QUnit.test( 'Error handling', function( assert ) {
		var parserFactory = new vp.ValueParserFactory();

		assert.throws(
			function() {
				parserFactory.registerParser( StringValue.TYPE, 'invalid' );
			},
			'Failed trying to register an invalid parser constructor.'
		);

		assert.throws(
			function() {
				parserFactory.register( 'invalid', StringParser );
			},
			'Failed trying to register a parser with an invalid purpose.'
		);

		parserFactory.registerParser( StringValue.TYPE, StringParser );

		assert.throws(
			function() {
				parserFactory.getParser( StringValue );
			},
			'Failed trying to get a parser with an invalid purpose.'
		);
	} );

	QUnit.test( 'Return default parser on getParser()', function( assert ) {
		var parserFactory = new vp.ValueParserFactory( BoolParser );

		assert.equal(
			parserFactory.getParser( StringValue.TYPE ),
			BoolParser,
			'Returning default parser if no parser is registered for a specific data value.'
		);

		parserFactory.registerParser( StringValue.TYPE, StringParser );

		assert.equal(
			parserFactory.getParser( StringValue.TYPE ),
			StringParser,
			'Returning specific parser if a parser is registered for a specific data value.'
		);

		assert.equal(
			parserFactory.getParser( BoolValue.TYPE ),
			BoolParser,
			'Still returning default parser if no parser is registered for a specific data value.'
		);
	} );

	// Tests regarding registration of parsers:

	/**
	 * Array of test definitions used as provider for "valueParserFactoryRegistrationTest".
	 * @type {Object[]}
	 */
	var valueParserFactoryRegistrationTestCases = [
		{
			title: 'Empty ValueParserFactory',
			register: [],
			expect: [
				[ StringValue.TYPE, null ],
				[ stringType, null ]
			]
		},
		{
			title: 'Factory with parser for string DataValue which is also suitable for string '
				+ 'DataType',
			register: [
				[ StringValue.TYPE, StringParser ]
			],
			expect: [
				[ StringValue.TYPE, StringParser ],
				[ stringType, StringParser ], // data type uses value type
				[ BoolValue.TYPE, null ],
				[ numberType, null ]
			]
		},
		{
			title: 'Factory for string DataType. String DataValue can\'t use this potentially more '
				+ 'specialized parser',
			register: [
				[ stringType, StringParser ]
			],
			expect: [
				[ StringValue.TYPE, null ],
				[ stringType, StringParser ]
			]
		},
		{
			title: 'Factory with two parsers: For DataValue and for DataType using that DataValue '
				+ 'type',
			register: [
				[ StringValue.TYPE, StringParser ],
				[ stringType, StringParser ]
			],
			expect: [
				[ StringValue.TYPE, StringParser ],
				[ stringType, StringParser ],
				[ BoolValue.TYPE, null ]
			]
		},
		{
			title: 'Factory with two parsers for two different DataValue types',
			register: [
				[ StringValue.TYPE, StringParser ],
				[ BoolValue.TYPE, BoolParser ]
			],
			expect: [
				[ StringValue.TYPE, StringParser ],
				[ BoolValue.TYPE, BoolParser ],
				[ numberType, null ]
			]
		}
	];

	/**
	 * Test for registration of ValueParsers to ValueParserFactory and expected conditions
	 * afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays where each one tells a ValueParserFactory
	 *        what parsers to register. The inner array has to consist out of two objects as
	 *        ValueParserFactory.registerParser would take them.
	 * @param {Array[]} toExpect Array containing arrays where each one states one expected
	 *        condition of the ValueParserFactory after registration of what is given in the first
	 *        parameter. Each inner array should contain a data type, data value or data value
	 *        constructor and a ValueParser which is expected to be registered for it.
	 */
	function valueParserFactoryRegistrationTest( assert, toRegister, toExpect  ) {
		var parserFactory = new vp.ValueParserFactory();

		// Register ValueParsers as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var purpose = registerPair[0],
				parser = registerPair[1];

			parserFactory.registerParser( purpose, parser );

			assert.ok(
				true,
				'Registered parser for ' + getTypeInfo( purpose )
			);
		} );

		// Check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var purpose = expectPair[0],
				parser = expectPair[1];

			assert.strictEqual(
				parserFactory.getParser( purpose ),
				parser,
				'Requesting parser for ' + getTypeInfo( purpose ) +
					( parser !== null ? ' returns expected parser' : ' returns null' )
			);
		} );
	}

	QUnit
	.cases( valueParserFactoryRegistrationTestCases )
		.test( 'registerParser() & getParser() ', function( params, assert ) {
			valueParserFactoryRegistrationTest( assert, params.register, params.expect );
		} );

}( jQuery, QUnit, dataTypes, dataValues, valueParsers ) );
