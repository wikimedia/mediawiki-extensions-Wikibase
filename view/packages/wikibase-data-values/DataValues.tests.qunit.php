<?php
/**
 * Definition of 'DataValues' qunit test modules.
 * When included this returns an array with all qunit test module definitions. Given file patchs
 * are relative to the package's root.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	// base path from package root:
	$bp = 'tests';

	return array(
		'dataValues.tests' => array(
			'scripts' => array(
				"$bp/dataValues.tests.js",
			),
			'dependencies' => array(
				'dataValues',
			),
		),

		'dataValues.DataValue.tests' => array(
			'scripts' => array(
				"$bp/dataValues.DataValue.tests.js",
			),
			'dependencies' => array(
				'dataValues.DataValue',
			),
		),

		'dataValues.values.tests' => array(
			'scripts' => array(
				"$bp/values/BoolValue.tests.js",
				"$bp/values/DecimalValue.tests.js",
				"$bp/values/GlobeCoordinateValue.tests.js",
				"$bp/values/MonolingualTextValue.tests.js",
				"$bp/values/MultilingualTextValue.tests.js",
				"$bp/values/StringValue.tests.js",
				"$bp/values/NumberValue.tests.js",
				"$bp/values/TimeValue.tests.js",
				"$bp/values/QuantityValue.tests.js",
				"$bp/values/UnknownValue.tests.js",
				"$bp/values/UnUnserializableValue.tests.js",
			),
			'dependencies' => array(
				'dataValues.DataValue.tests',
				'dataValues.values'
			),
		),

		'dataValues.util.tests' => array(
			'scripts' => array(
				"$bp/util.inherit/inherit.testWithDifferentArguments.js",
				"$bp/util.inherit/inherit.testConstructorNames.js",
				"$bp/util.inherit/inherit.testGeneratedConstructorNames.js",
			),
			'dependencies' => array(
				'dataValues.util',
			),
		),

		'globeCoordinate.js.tests' => array(
			'scripts' => array(
				'resources/globeCoordinate.js/tests/globeCoordinate.tests.js',
				'resources/globeCoordinate.js/tests/globeCoordinate.Formatter.tests.js',
				'resources/globeCoordinate.js/tests/globeCoordinate.GlobeCoordinate.tests.js',
			),
			'dependencies' => array(
				'globeCoordinate.js',
			),
		),

		// tests of Time.js:
		'time.js.tests' => array(
			'scripts' => array(
				'resources/time.js/tests/time.Time.knowsPrecision.tests.js',
				'resources/time.js/tests/time.Time.minPrecision.tests.js',
				'resources/time.js/tests/time.Time.maxPrecision.tests.js',
				'resources/time.js/tests/time.Time.tests.js',
				'resources/time.js/tests/time.Time.validate.tests.js',
				'resources/time.js/tests/time.Parser.tests.js',
				'resources/time.js/tests/time.Time.newFromIso8601.tests.js',
			),
			'dependencies' => array(
				'time.js',
				'time.js.validTimeDefinitions',
			),
		),

	);

} );
// @codeCoverageIgnoreEnd
