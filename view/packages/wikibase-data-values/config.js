/**
 * RequireJS configuration
 * Basic RequireJS configuration object expanded with the list of test modules.
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
this.config = ( function() {
	'use strict';

	return {
		baseUrl: '..',
		paths: {
			jquery: 'lib/jquery/jquery',
			qunit: 'lib/qunit/qunit',
			'qunit.parameterize': 'lib/qunit.parameterize/qunit.parameterize',

			util: 'lib/util',

			globeCoordinate: 'lib/globeCoordinate',

			dataValues: 'src',
			values: 'src/values',

			valueFormatters: 'src/valueFormatters',
			formatters: 'src/valueFormatters/formatters',

			valueParsers: 'src/valueParsers',
			parsers: 'src/valueParsers/parsers'
		},
		shim: {
			qunit: {
				exports: 'QUnit',
				init: function() {
					QUnit.config.autoload = false;
					QUnit.config.autostart = false;
				}
			},
			'qunit.parameterize': {
				exports: 'QUnit.cases',
				deps: ['qunit']
			},

			'util/util.inherit': {
				exports: 'util'
			},

			'globeCoordinate/globeCoordinate': {
				exports: 'globeCoordinate'
			},
			'globeCoordinate/globeCoordinate.GlobeCoordinate': {
				exports: 'globeCoordinate.GlobeCoordinate',
				deps: ['globeCoordinate/globeCoordinate']
			},
			'globeCoordinate/globeCoordinate.Formatter': {
				exports: 'globeCoordinate.Formatter',
				deps: ['globeCoordinate/globeCoordinate']
			},

			'dataValues/dataValues': {
				exports: 'dataValues'
			},
			'dataValues/DataValue': ['dataValues/dataValues', 'jquery', 'util/util.inherit'],

			'values/BoolValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/DecimalValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/GlobeCoordinateValue': [
				'dataValues/dataValues',
				'jquery',
				'dataValues/DataValue',
				'util/util.inherit',
				'globeCoordinate/globeCoordinate.GlobeCoordinate',
				'globeCoordinate/globeCoordinate.Formatter'
			],
			'values/MonolingualTextValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/MultilingualTextValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/StringValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/NumberValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/TimeValue': [
				'dataValues/dataValues',
				'jquery',
				'dataValues/DataValue',
				'util/util.inherit'
			],
			'values/QuantityValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/UnknownValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],
			'values/UnDeserializableValue': [
				'dataValues/dataValues', 'jquery', 'dataValues/DataValue', 'util/util.inherit'
			],

			'valueFormatters/valueFormatters': {
				exports: 'valueFormatters'
			},
			'valueFormatters/ValueFormatterStore': {
				exports: 'valueFormatters.ValueFormatterStore',
				deps: ['valueFormatters/valueFormatters', 'jquery']
			},

			'formatters/NullFormatter': [
				'valueFormatters/valueFormatters',
				'util/util.inherit',
				'jquery',
				'dataValues/dataValues',
				'formatters/ValueFormatter',
				'dataValues/DataValue',
				'values/UnknownValue'
			],
			'formatters/StringFormatter': [
				'valueFormatters/valueFormatters',
				'util/util.inherit',
				'jquery',
				'formatters/ValueFormatter'
			],
			'formatters/ValueFormatter': [
				'valueFormatters/valueFormatters', 'util/util.inherit', 'jquery'
			],

			'valueParsers/valueParsers': {
				exports: 'valueParsers'
			},
			'valueParsers/ValueParserStore': {
				exports: 'valueParsers.ValueParserStore',
				deps: ['valueParsers/valueParsers', 'jquery']
			},

			'parsers/BoolParser': [
				'valueParsers/valueParsers',
				'dataValues/dataValues',
				'util/util.inherit',
				'jquery',
				'parsers/ValueParser',
				'values/BoolValue'
			],
			'parsers/NullParser': [
				'valueParsers/valueParsers',
				'dataValues/dataValues',
				'util/util.inherit',
				'jquery',
				'parsers/ValueParser',
				'values/UnknownValue'
			],
			'parsers/StringParser': [
				'valueParsers/valueParsers',
				'dataValues/dataValues',
				'util/util.inherit',
				'jquery',
				'parsers/ValueParser',
				'values/StringValue'
			],
			'parsers/ValueParser': ['valueParsers/valueParsers', 'util/util.inherit', 'jquery'],

			// TODO: These tests should not require any specific DataValue constructor but rather
			// use mocks. Properly define the module after removing the dependencies:
			'dataValues.tests': [
				'jquery', 'dataValues/dataValues', 'qunit',
				'values/BoolValue',
				'values/DecimalValue',
				'values/GlobeCoordinateValue',
				'values/MonolingualTextValue',
				'values/MultilingualTextValue',
				'values/StringValue',
				'values/NumberValue',
				'values/TimeValue',
				'values/QuantityValue',
				'values/UnknownValue',
				'values/UnDeserializableValue'
			],

			// Shim test modules that external components depend on:
			'tests/src/valueParsers/valueParsers.tests': [
				'valueParsers/valueParsers',
				'dataValues/dataValues',
				'util/util.inherit',
				'jquery',
				'qunit'
			],

			'tests/src/valueFormatters/valueFormatters.tests': [
				'valueFormatters/valueFormatters',
				'dataValues/dataValues',
				'util/util.inherit',
				'jquery',
				'qunit'
			]
		},
		tests: [
			'tests/lib/globeCoordinate/globeCoordinate.tests',
			'tests/lib/globeCoordinate/globeCoordinate.Formatter.tests',
			'tests/lib/globeCoordinate/globeCoordinate.GlobeCoordinate.tests',

			'tests/src/dataValues.tests',

			'tests/src/values/BoolValue.tests',
			'tests/src/values/DecimalValue.tests',
			'tests/src/values/GlobeCoordinateValue.tests',
			'tests/src/values/MonolingualTextValue.tests',
			'tests/src/values/MultilingualTextValue.tests',
			'tests/src/values/StringValue.tests',
			'tests/src/values/NumberValue.tests',
			'tests/src/values/TimeValue.tests',
			'tests/src/values/QuantityValue.tests',
			'tests/src/values/UnknownValue.tests',
			'tests/src/values/UnDeserializableValue.tests',

			'tests/src/valueFormatters/valueFormatters.tests',
			'tests/src/valueFormatters/ValueFormatterStore.tests',

			'tests/src/valueFormatters/formatters/NullFormatter.tests',
			'tests/src/valueFormatters/formatters/StringFormatter.tests',

			'tests/src/valueParsers/valueParsers.tests',
			'tests/src/valueParsers/ValueParserStore.tests',

			'tests/src/valueParsers/parsers/NullParser.tests',
			'tests/src/valueParsers/parsers/StringParser.tests'
		]
	};

} )();
