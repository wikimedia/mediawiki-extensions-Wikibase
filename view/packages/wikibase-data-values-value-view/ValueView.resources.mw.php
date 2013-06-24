<?php
/**
 * Definition of "ValueView" resourceloader modules.
 * When included this returns an array with all modules introduced by the "valueview" jQuery
 * extension.
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

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' =>  'DataValues/ValueView/resources',
	);

	$mwVvResources = array(
		'mw.ext.valueView' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueView.js',
			),
			'dependencies' => array(
				'jquery.valueview',
				'jquery.valueview.experts.stringvalue',
				'jquery.valueview.experts.globecoordinatevalue',
				'jquery.valueview.experts.timevalue',
				'jquery.valueview.experts.commonsmediatype'
			),
		),

		// Dependencies required by jQuery.valueview library:
		'jquery.eachchange' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.eachchange.js'
			),
			'dependencies' => array(
				'jquery.client'
			)
		),

		'jquery.inputAutoExpand' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.inputAutoExpand.js',
			),
			'dependencies' => array(
				'jquery.eachchange'
			)
		),

		'jquery.fn.focusAt' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.fn.focusAt.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.ui.suggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.suggester.js'
			),
			'styles' => array(
				'jquery.ui/jquery.ui.suggester.css'
			),
			'dependencies' => array(
				'jquery.ui.autocomplete'
			)
		),

		'jquery.time.timeinput' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.time/jquery.time.timeinput.js',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.eachchange',
				'time.js'
			),
		),

		'jquery.ui.inputextender' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.inputextender.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.inputextender.css',
			),
			'dependencies' => array(
				'jquery.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.toggler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.toggler.js',
			),
			'styles' => array(
				'jquery.ui/themes/default/jquery.ui.toggler.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
			),
		),


		'jquery.ui.listrotator' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.listrotator.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.listrotator.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.ui.position',
				'jquery.ui.autocomplete', // needs jquery.ui.menu
			),
			'messages' => array(
				'valueview-listrotator-auto',
			),
		),
	);

	// return jQuery.valueview's native resources plus those required by the MW extension:
	return $mwVvResources + include( __DIR__ . '/ValueView.resources.php' );
} );
// @codeCoverageIgnoreEnd
