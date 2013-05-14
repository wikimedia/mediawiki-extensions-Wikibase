<?php
/**
 * Definition of 'DataValues' resourceloader modules.
 * When included this returns an array with all the modules introduced by 'DataValues' extension.
 *
 * External dependencies:
 * - jQuery 1.8
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

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' =>  'DataValues/DataValues/resources',
	);

	return array(
		'dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.js',
			),
		),

		'dataValues.DataValue' => $moduleTemplate + array(
			'scripts' => array(
				'DataValue.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.util',
			),
		),

		'dataValues.values' => $moduleTemplate + array(
			'scripts' => array(
				// Note: The order here is relevant, scripts should be places after the ones they
				//  depend on.
				// TODO: Make one module per data value type.
				'values/BoolValue.js',
				'values/MonolingualTextValue.js',
				'values/MultilingualTextValue.js',
				'values/StringValue.js',
				'values/NumberValue.js',
				'values/TimeValue.js',
				'values/UnknownValue.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'time.js' // required by TimeValue
			),
		),

		'dataValues.util' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.util.js',
				'dataValues.util.inherit.js',
				'dataValues.util.Notify.js',
			),
			'dependencies' => array(
				'dataValues',
			),
		),

		// time.js
		'time.js' => $moduleTemplate + array(
			'scripts' => array(
				'time.js/src/time.js',
				'time.js/src/time.Time.js',
				'time.js/src/time.Time.parse.js',
			)
		),

		// qunit-parameterize from https://github.com/AStepaniuk/qunit-parameterize
		'qunit.parameterize' => $moduleTemplate + array(
			'scripts' => array(
				'qunit.parameterize/qunit.parameterize.js',
			),
			'dependencies' => array(
				'jquery.qunit'
			)
		),
	);

} );
// @codeCoverageIgnoreEnd
