<?php
/**
 * Definition of 'ValueView' qunit test modules.
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
 * @ingroup ValueView
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
		'jquery.eachchange.tests' => array(
			'scripts' => array(
				"$bp/jquery/jquery.eachchange.tests.js",
			),
			'dependencies' => array(
				'jquery.eachchange',
			),
		),

		'jquery.inputAutoExpand.tests' => array(
			'scripts' => array(
				"$bp/jquery/jquery.inputAutoExpand.tests.js",
			),
			'dependencies' => array(
				'jquery.inputAutoExpand',
			),
		),

		'jquery.ui.suggester.tests' => array(
			'scripts' => array(
				"$bp/jquery.ui/jquery.ui.suggester.tests.js",
			),
			'dependencies' => array(
				'jquery.ui.suggester',
			),
		),

		'jquery.valueview.ExpertFactory.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.ExpertFactory.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts', // contains ExpertFactory
				'jquery.valueview.experts.mock',
				'qunit.parameterize'
			),
		)
	);

} );
// @codeCoverageIgnoreEnd
