/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
define( [
	'valueParsers/valueParsers',
	'dataValues/dataValues',
	'jquery',
	'qunit',
	'values/NumberValue',
	'values/StringValue',
	'values/UnknownValue',
	'parsers/NullParser',
	'parsers/StringParser',
	'valueParsers/ValueParserStore',
	'qunit.parameterize'
], function( vp, dv, $, QUnit ) {
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
	 * Returns a descriptive string to be used as id when registering a ValueParser in a
	 * ValueParserStore.
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
		StringParser = vp.StringParser,
		NullParser = vp.NullParser;

	QUnit.module( 'valueParsers.ValueParserStore' );

	QUnit.test( 'Constructor', function( assert ) {
		var parserStore = new vp.ValueParserStore();

		assert.ok(
			parserStore instanceof vp.ValueParserStore,
			'Instantiated ValueParserStore.'
		);
	} );

	QUnit.test( 'registerDataTypeParser(): Error handling', function( assert ) {
		var parserStore = new vp.ValueParserStore();

		assert['throws'](
			function() {
				parserStore.registerDataTypeParser( 'invalid', stringType.getId() );
			},
			'Failed trying to register an invalid parser constructor.'
		);

		parserStore.registerDataTypeParser( StringParser, stringType.getId() );

		assert['throws'](
			function() {
				parserStore.getParser( stringType );
			},
			'Failed trying to get a parser with an invalid purpose.'
		);
	} );

	QUnit.test( 'registerDataValueParser(): Error handling', function( assert ) {
		var parserStore = new vp.ValueParserStore();

		assert['throws'](
			function() {
				parserStore.registerDataValueParser( 'invalid', StringValue.TYPE );
			},
			'Failed trying to register an invalid parser constructor.'
		);

		parserStore.registerDataValueParser( StringParser, StringValue.TYPE );

		assert['throws'](
			function() {
				parserStore.getParser( StringValue );
			},
			'Failed trying to get a parser with an invalid purpose.'
		);
	} );

	QUnit.test( 'Return default parser on getParser()', function( assert ) {
		var parserStore = new vp.ValueParserStore( NullParser );

		assert.equal(
			parserStore.getParser( StringValue.TYPE ),
			NullParser,
			'Returning default parser if no parser is registered for a specific data value.'
		);

		assert.equal(
			parserStore.getParser( stringType.getDataValueType(), stringType.getId() ),
			NullParser,
			'Returning default parser if no parser is registered for a specific data type.'
		);

		parserStore.registerDataValueParser( StringParser, StringValue.TYPE );

		assert.equal(
			parserStore.getParser( StringValue.TYPE ),
			StringParser,
			'Returning specific parser if a parser is registered for a specific data value.'
		);

		assert.equal(
			parserStore.getParser( UnknownValue.TYPE ),
			NullParser,
			'Still returning default parser if no parser is registered for a specific data value.'
		);

		assert.equal(
			parserStore.getParser( numberType.getDataValueType(), numberType.getId() ),
			NullParser,
			'Still returning default parser if no parser is registered for a specific data type.'
		);
	} );

	// Tests regarding registration of parsers:

	/**
	 * Array of test definitions used as provider for "valueParserStoreRegistrationTest".
	 * @property {Object[]}
	 */
	var valueParserStoreRegistrationTestCases = [
		{
			title: 'Empty ValueParserStore',
			register: [],
			expect: [
				[ StringValue, null ],
				[ stringType, null ]
			]
		},
		{
			title: 'Store with parser for string DataValue which is also suitable for string '
				+ 'DataType',
			register: [
				[ StringValue, StringParser ]
			],
			expect: [
				[ StringValue, StringParser ],
				[ stringType, StringParser ], // data type uses value type
				[ UnknownValue, null ],
				[ numberType, null ]
			]
		},
		{
			title: 'Store for string DataType. String DataValue can\'t use this potentially more '
				+ 'specialized parser',
			register: [
				[ stringType, StringParser ]
			],
			expect: [
				[ StringValue, null ],
				[ stringType, StringParser ]
			]
		},
		{
			title: 'Store with two parsers: For DataValue and for DataType using that DataValue '
				+ 'type',
			register: [
				[ StringValue, StringParser ],
				[ stringType, StringParser ]
			],
			expect: [
				[ StringValue, StringParser ],
				[ stringType, StringParser ],
				[ UnknownValue, null ]
			]
		},
		{
			title: 'Store with two parsers for two different DataValue types',
			register: [
				[ StringValue, StringParser ],
				[ UnknownValue, NullParser ]
			],
			expect: [
				[ StringValue, StringParser ],
				[ UnknownValue, NullParser ],
				[ numberType, null ]
			]
		}
	];

	/**
	 * Test for registration of ValueParsers to ValueParserStore and expected conditions
	 * afterwards.
	 *
	 * @param {QUnit.assert} assert
	 * @param {Array[]} toRegister Array containing arrays where each one tells a ValueParserStore
	 *        what parsers to register. The inner array has to consist out of two objects, a parser
	 *        constructor and a DataValue constructor or a DataTypeMock object.
	 * @param {Array[]} toExpect Array containing arrays where each one states one expected
	 *        condition of the ValueParserStore after registration of what is given in the first
	 *        parameter. Each inner array should contain a data type, data value or data value
	 *        constructor and a ValueParser which is expected to be registered for it.
	 */
	function valueParserStoreRegistrationTest( assert, toRegister, toExpect ) {
		var parserStore = new vp.ValueParserStore();

		// Register ValueParsers as per definition:
		$.each( toRegister, function( i, registerPair ) {
			var purpose = registerPair[0],
				Parser = registerPair[1];

			if( purpose instanceof DataTypeMock ) {
				parserStore.registerDataTypeParser( Parser, purpose.getId() );
			} else {
				parserStore.registerDataValueParser( Parser, purpose.TYPE );
			}

			assert.ok(
				true,
				'Registered parser for ' + getTypeInfo( purpose )
			);
		} );

		// Check for expected conditions:
		$.each( toExpect, function( i, expectPair ) {
			var purpose = expectPair[0],
				Parser = expectPair[1],
				RetrievedParser;

			if( purpose instanceof DataTypeMock ) {
				RetrievedParser = parserStore.getParser(
					purpose.getDataValueType(), purpose.getId()
				);
			} else {
				RetrievedParser = parserStore.getParser( purpose.TYPE );
			}

			assert.strictEqual(
				RetrievedParser,
				Parser,
				'Requesting parser for ' + getTypeInfo( purpose ) +
					( Parser !== null ? ' returns expected parser' : ' returns null' )
			);
		} );
	}

	QUnit
	.cases( valueParserStoreRegistrationTestCases )
		.test(
			'registerDataTypeParser() / registerDataValueParser() & getParser() ',
			function( params, assert ) {
				valueParserStoreRegistrationTest( assert, params.register, params.expect );
			}
		);

} );
