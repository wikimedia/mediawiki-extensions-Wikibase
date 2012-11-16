<?php
/**
 * Definition of 'DataValues' resourceloader modules.
 * When included this returns an array with all the modules introduced by 'DataValues' extension.
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
 * @author Daniel Werner
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
				// Note: the order here is relevant, scripts should be places after the ones they depend on
				'values/BoolValue.js',
				'values/MonolingualTextValue.js',
				'values/MultilingualTextValue.js',
				'values/StringValue.js',
				'values/UnknownValue.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
			),
		),

		'dataValues.util' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.util.js',
			),
			'dependencies' => array(
				'dataValues',
			),
		),
	);

} );
// @codeCoverageIgnoreEnd
