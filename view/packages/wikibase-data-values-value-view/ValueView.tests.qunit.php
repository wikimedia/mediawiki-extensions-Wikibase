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

		'jquery.fn.focusAt.tests' => array(
			'scripts' => array(
				"$bp/jquery/jquery.fn.focusAt.tests.js",
			),
			'dependencies' => array(
				'jquery.fn.focusAt',
				'qunit.parameterize',
			),
		),

		'jquery.time.timeinput.tests' => array(
			'scripts' => array(
				"$bp/jquery.time/jquery.time.timeinput.tests.js",
			),
			'dependencies' => array(
				'jquery.time.timeinput',
			),
		),

		'jquery.ui.inputextender.tests' => array(
			'scripts' => array(
				"$bp/jquery.ui/jquery.ui.inputextender.tests.js",
			),
			'dependencies' => array(
				'jquery.ui.inputextender',
			),
		),

		'jquery.ui.listrotator.tests' => array(
			'scripts' => array(
				"$bp/jquery.ui/jquery.ui.listrotator.tests.js",
			),
			'dependencies' => array(
				'jquery.ui.listrotator',
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

		'jquery.valueview.preview.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.preview.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.preview',
			),
		),

		'jquery.ui.toggler.tests' => array(
			'scripts' => array(
				"$bp/jquery.ui/jquery.ui.toggler.tests.js",
			),
			'dependencies' => array(
				'jquery.ui.toggler',
			),
		),

		'jquery.valueview.MockViewState.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.MockViewState.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.MockViewState',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.ExpertFactory.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.ExpertFactory.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts', // contains ExpertFactory
				'jquery.valueview.experts.mock',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.tests.testExpert.js' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.tests.testExpert.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.MessageProvider.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.MessageProvider.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.MessageProvider',
			),
		),

		'jquery.valueview.experts.stringvalue.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.experts/experts.StringValue.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts.stringvalue',
			),
		),

		'jquery.valueview.experts.globecoordinateinput.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.experts/experts.GlobeCoordinateInput.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts.globecoordinateinput',
			),
		),

		'jquery.valueview.experts.timeinput.tests' => array(
			'scripts' => array(
				"$bp/jquery.valueview/valueview.experts/experts.TimeInput.tests.js",
			),
			'dependencies' => array(
				'jquery.valueview.experts.timeinput',
			),
		),

		'jquery.autocompletestring.tests' => array(
			'scripts' => array(
				"$bp/jquery/jquery.autocompletestring.tests.js",
			),
			'dependencies' => array(
				'jquery.autocompletestring',
			),
		),

		'jquery.util.adaptlettercase.tests' => array(
			'scripts' => array(
				"$bp/jquery.util/jquery.util.adaptlettercase.tests.js",
			),
			'dependencies' => array(
				'jquery.util.adaptlettercase',
			),
		),

	);

} );
// @codeCoverageIgnoreEnd
