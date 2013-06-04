<?php
/**
 * Definition of 'DataValues' qunit test modules.
 * When included this returns an array with all qunit test module definitions. Given file patchs
 * are relative to the package's root.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
	$bp = 'tests/qunit';

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
				"$bp/values/GlobeCoordinateValue.tests.js",
				"$bp/values/MonolingualTextValue.tests.js",
				"$bp/values/MultilingualTextValue.tests.js",
				"$bp/values/StringValue.tests.js",
				"$bp/values/NumberValue.tests.js",
				"$bp/values/TimeValue.tests.js",
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
				"$bp/dataValues.util.inherit.tests.js",
				"$bp/dataValues.util.Notifier.tests.js",
			),
			'dependencies' => array(
				'dataValues.util',
			),
		),

		'globeCoordinate.js.tests' => array(
			'scripts' => array(
				'resources/globeCoordinate.js/tests/globeCoordinate.tests.js',
				'resources/globeCoordinate.js/tests/globeCoordinate.parser.tests.js',
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
