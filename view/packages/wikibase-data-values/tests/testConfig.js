/**
 * require.js configuration
 * In addition to the test files, the configuration has to feature the source JavaScript files as
 * well in order to be able to specify dependencies.
 * (If, at some point, require.js is used for source files as well, this configuration file should
 * be moved up in the directory hierarchy.)
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
var testConfig = ( function() {
	'use strict';

	return {
		baseUrl: '..',
		paths: {
			jquery: 'lib/jquery/jquery',
			qunit: 'lib/qunit/qunit',
			'qunit.parameterize': 'lib/qunit.parameterize/qunit.parameterize',

			'util.inherit': 'lib/util/util.inherit',

			'globeCoordinate': 'lib/globeCoordinate/globeCoordinate',
			'globeCoordinate.Formatter': 'lib/globeCoordinate/globeCoordinate.Formatter',
			'globeCoordinate.GlobeCoordinate': 'lib/globeCoordinate/globeCoordinate.GlobeCoordinate',

			'time': 'lib/time/time',
			'time.Parser': 'lib/time/time.Parser',
			'time.Time': 'lib/time/time.Time',
			'time.Time.validate': 'lib/time/time.Time.validate',

			'dataValues': 'src/dataValues',
			'dataValues.DataValue': 'src/DataValue',

			'dataValues.BoolValue': 'src/values/BoolValue',
			'dataValues.DecimalValue': 'src/values/DecimalValue',
			'dataValues.GlobeCoordinateValue': 'src/values/GlobeCoordinateValue',
			'dataValues.MonolingualTextValue': 'src/values/MonolingualTextValue',
			'dataValues.MultilingualTextValue': 'src/values/MultilingualTextValue',
			'dataValues.StringValue': 'src/values/StringValue',
			'dataValues.NumberValue': 'src/values/NumberValue',
			'dataValues.TimeValue': 'src/values/TimeValue',
			'dataValues.QuantityValue': 'src/values/QuantityValue',
			'dataValues.UnknownValue': 'src/values/UnknownValue',
			'dataValues.UnUnserializableValue': 'src/values/UnUnserializableValue',

			'valueFormatters': 'src/valueFormatters/valueFormatters',
			'valueFormatters.ValueFormatterFactory': 'src/valueFormatters/ValueFormatterFactory',

			'valueFormatters.NullFormatter': 'src/valueFormatters/formatters/NullFormatter',
			'valueFormatters.StringFormatter': 'src/valueFormatters/formatters/StringFormatter',
			'valueFormatters.ValueFormatter': 'src/valueFormatters/formatters/ValueFormatter',

			'valueParsers': 'src/valueParsers/valueParsers',
			'valueParsers.ValueParserFactory': 'src/valueParsers/ValueParserFactory',

			'valueParsers.BoolParser': 'src/valueParsers/parsers/BoolParser',
			'valueParsers.FloatParser': 'src/valueParsers/parsers/FloatParser',
			'valueParsers.IntParser': 'src/valueParsers/parsers/IntParser',
			'valueParsers.NullParser': 'src/valueParsers/parsers/NullParser',
			'valueParsers.StringParser': 'src/valueParsers/parsers/StringParser',
			'valueParsers.TimeParser': 'src/valueParsers/parsers/TimeParser',
			'valueParsers.ValueParser': 'src/valueParsers/parsers/ValueParser',


			'util.inherit.tests': 'tests/lib/util/util.inherit.tests',

			'globeCoordinate.tests': 'tests/lib/globeCoordinate/globeCoordinate.tests',
			'globeCoordinate.Formatter.tests': 'tests/lib/globeCoordinate/globeCoordinate.Formatter.tests',
			'globeCoordinate.GlobeCoordinate.tests': 'tests/lib/globeCoordinate/globeCoordinate.GlobeCoordinate.tests',

			'time.validTimeDefinitions': 'tests/lib/time/time.validTimeDefinitions',
			'time.Parser.tests': 'tests/lib/time/time.Parser.tests',
			'time.Time.knowsPrecision.tests': 'tests/lib/time/time.Time.knowsPrecision.tests',
			'time.Time.maxPrecision.tests': 'tests/lib/time/time.Time.maxPrecision.tests',
			'time.Time.minPrecision.tests': 'tests/lib/time/time.Time.minPrecision.tests',
			'time.Time.newFromIso8601.tests': 'tests/lib/time/time.Time.newFromIso8601.tests',
			'time.Time.tests': 'tests/lib/time/time.Time.tests',
			'time.Time.validate.tests': 'tests/lib/time/time.Time.validate.tests',

			'dataValues.tests': 'tests/src/dataValues.tests',
			'dataValues.DataValue.tests': 'tests/src/dataValues.DataValue.tests',

			'BoolValue.tests': 'tests/src/values/BoolValue.tests',
			'DecimalValue.tests': 'tests/src/values/DecimalValue.tests',
			'GlobeCoordinateValue.tests': 'tests/src/values/GlobeCoordinateValue.tests',
			'MonolingualTextValue.tests': 'tests/src/values/MonolingualTextValue.tests',
			'MultilingualTextValue.tests': 'tests/src/values/MultilingualTextValue.tests',
			'StringValue.tests': 'tests/src/values/StringValue.tests',
			'NumberValue.tests': 'tests/src/values/NumberValue.tests',
			'TimeValue.tests': 'tests/src/values/TimeValue.tests',
			'QuantityValue.tests': 'tests/src/values/QuantityValue.tests',
			'UnknownValue.tests': 'tests/src/values/UnknownValue.tests',
			'UnUnserializableValue.tests': 'tests/src/values/UnUnserializableValue.tests',

			'valueFormatters.tests': 'tests/src/valueFormatters/valueFormatters.tests',
			'valueFormatters.ValueFormatterFactory.tests': 'tests/src/valueFormatters/ValueFormatterFactory.tests',

			'valueFormatters.NullFormatter.tests': 'tests/src/valueFormatters/formatters/NullFormatter.tests',
			'valueFormatters.StringFormatter.tests': 'tests/src/valueFormatters/formatters/StringFormatter.tests',

			'valueParsers.tests': 'tests/src/valueParsers/valueParsers.tests',
			'valueParsers.ValueParserFactory.tests': 'tests/src/valueParsers/ValueParserFactory.tests',

			'valueParsers.NullParser.tests': 'tests/src/valueParsers/parsers/NullParser.tests',
			'valueParsers.StringParser.tests': 'tests/src/valueParsers/parsers/StringParser.tests',
			'valueParsers.TimeParser.tests': 'tests/src/valueParsers/parsers/TimeParser.tests'

		},
		shim: {
			jquery: {
				exports: 'jQuery'
			},
			qunit: {
				exports: 'QUnit',
				init: function() {
					QUnit.config.autoload = false;
					QUnit.config.autostart = false;
				}
			},
			'qunit.parameterize': {
				exports: 'QUnit.cases'
			},

			'util.inherit': {
				exports: 'util'
			},

			'globeCoordinate': {
				exports: 'globeCoordinate'
			},
			'globeCoordinate.GlobeCoordinate': {
				exports: 'globeCoordinate.GlobeCoordinate',
				deps: ['globeCoordinate']
			},
			'globeCoordinate.Formatter': {
				exports: 'globeCoordinate.Formatter',
				deps: ['globeCoordinate']
			},

			'time': {
				exports: 'time'
			},
			'time.Parser': {
				exports: 'time.Parser',
				deps: ['time']
			},
			'time.Time': {
				exports: 'time.Time',
				deps: ['time', 'time.Parser']
			},
			'time.Time.validate': {
				exports: 'time.Time.validate',
				deps: ['time.Time']
			},

			dataValues: {
				exports: 'dataValues',
				deps: ['jquery']
			},
			'dataValues.DataValue': {
				deps: ['dataValues', 'util.inherit']
			},

			'dataValues.BoolValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.DecimalValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.GlobeCoordinateValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit',
					'globeCoordinate.GlobeCoordinate', 'globeCoordinate.Formatter']
			},
			'dataValues.MonolingualTextValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.MultilingualTextValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.StringValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.NumberValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.TimeValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit',
					'time.Parser', 'time.Time', 'time.Time.validate']
			},
			'dataValues.QuantityValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.UnknownValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},
			'dataValues.UnUnserializableValue': {
				deps: ['dataValues', 'jquery', 'dataValues.DataValue', 'util.inherit']
			},

			'valueFormatters': {
				exports: 'valueFormatters'
			},
			'valueFormatters.ValueFormatterFactory': {
				exports: 'valueFormatters.ValueFormatterFactory',
				deps: ['valueFormatters', 'jquery']
			},

			'valueFormatters.NullFormatter': {
				deps: ['valueFormatters', 'util.inherit', 'jquery', 'dataValues',
					'valueFormatters.ValueFormatter', 'dataValues.DataValue',
					'dataValues.UnknownValue']
			},
			'valueFormatters.StringFormatter': {
				deps: ['valueFormatters', 'util.inherit', 'jquery', 'valueFormatters.ValueFormatter']
			},
			'valueFormatters.ValueFormatter': {
				deps: ['valueFormatters', 'util.inherit', 'jquery']
			},

			'valueParsers': {
				exports: 'valueParsers'
			},
			'valueParsers.util': {
				exports: 'valueParsers.util',
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery']
			},
			'valueParsers.ValueParserFactory': {
				exports: 'valueParsers.ValueParserFactory',
				deps: ['valueParsers', 'jquery']
			},

			'valueParsers.BoolParser': {
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery',
					'valueParsers.ValueParser', 'dataValues.BoolValue']
			},
			'valueParsers.NullParser': {
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery',
					'valueParsers.ValueParser', 'dataValues.UnknownValue']
			},
			'valueParsers.StringParser': {
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery',
					'valueParsers.ValueParser', 'dataValues.StringValue']
			},
			'valueParsers.TimeParser': {
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery',
					'valueParsers.ValueParser', 'dataValues.TimeValue']
			},
			'valueParsers.ValueParser': {
				'deps': ['valueParsers', 'util.inherit', 'jquery']
			},

			// TODO: These tests should not require any specific DataValue constructor but rather
			// use mocks. Properly define the module after removing the dependencies:
			'dataValues.tests': {
				deps: ['jquery', 'dataValues', 'qunit',
					'dataValues.BoolValue',
					'dataValues.DecimalValue',
					'dataValues.GlobeCoordinateValue',
					'dataValues.MonolingualTextValue',
					'dataValues.MultilingualTextValue',
					'dataValues.StringValue',
					'dataValues.NumberValue',
					'dataValues.TimeValue',
					'dataValues.QuantityValue',
					'dataValues.UnknownValue',
					'dataValues.UnUnserializableValue'
				]
			},

			// Shim test modules that external components depend on:
			'valueParsers.tests': {
				deps: ['valueParsers', 'dataValues', 'util.inherit', 'jquery', 'qunit']
			},

			'valueFormatters.tests': {
				deps: ['valueFormatters', 'dataValues', 'util.inherit', 'jquery', 'qunit']
			}
		}
	};

} )();
