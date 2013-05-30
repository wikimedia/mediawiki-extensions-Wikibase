<?php
/**
 * Definition of "ValueView" resourceloader modules.
 * When included this returns an array with all modules introduced by the "valueview" jQuery
 * extension.
 *
 * External dependencies:
 * - jQuery 1.8
 * - jQuery.eachchange
 * - jQuery.inputAutoExpand
 * - jQuery.ui.suggester
 * - jQuery.coordinate.coordinateinput
 * - jQuery.time.timeinput
 * - jQuery.ui.toggler
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
		'remoteExtPath' =>  'DataValues/ValueView/resources',
	);

	return array(
		// The actual jQuery.valueview object, but put this into '.base' because 'jquery.valueview'
		// will overwrite this object with the Widget constructor when loaded. This module is just
		// good as a dependency for experts without them requiring a dependency on 'jquery.valueview'
		// which does require two of the experts.
		'jquery.valueview.base' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.base.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without loosing any properties.
		'jquery.valueview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.js',
			),
			'dependencies' => array(
				'jquery.valueview.base',
				'jquery.valueview.valueview',
			),
		),

		'jquery.valueview.ViewState' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.ViewState.js',
			),
			'dependencies' => array(
				'jquery.valueview.base',
			),
		),

		'jquery.valueview.MockViewState' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.MockViewState.js',
			),
			'dependencies' => array(
				'jquery.valueview.ViewState',
			),
		),

		// The actual valueview (vv) widget:
		'jquery.valueview.valueview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.valueview.js', // the actual widget definition
				'jquery.valueview/valueview.ViewState.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.valueview.base',
				'jquery.valueview.ViewState',
				'jquery.valueview.experts', // because vv deals with ExpertFactory
				'jquery.valueview.experts.unsupportedvalue', // for displaying unsupported values
				'jquery.valueview.experts.emptyvalue', // for displaying empty values
				// NOTE: don't add further experts here unless they are required by the valueview
			    // widget directly. All experts are supposed to be loaded separately, by demand and
				// by the controller requiring them.
			),
		),

		// Facility for creating valueview experts and the namespace for experts supported natively:
		'jquery.valueview.experts' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.ExpertFactory.js',
				'jquery.valueview/valueview.Expert.js',
				'jquery.valueview/valueview.experts/experts.js',
			),
			'dependencies' => array(
				'jquery.valueview.base',
				'dataValues.util',
				'dataValues.values',
				'dataTypes',
				'valueParsers.parsers',
			),
		),

		'jquery.valueview.BifidExpert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.BifidExpert.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
			),
		),

		// ACTUAL EXPERTS IMPLEMENTATIONS:
		'jquery.valueview.experts.unsupportedvalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.UnsupportedValue.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.experts/experts.UnsupportedValue.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
			),
			'messages' => array(
				'valueview-expert-unsupportedvalue-unsupporteddatavalue',
				'valueview-expert-unsupportedvalue-unsupporteddatatype',
			)
		),

		'jquery.valueview.experts.emptyvalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.EmptyValue.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.experts/experts.EmptyValue.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
			),
			'messages' => array(
				'valueview-expert-emptyvalue-empty',
			)
		),

		'jquery.valueview.experts.mock' => $moduleTemplate + array( // mock expert for tests
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.Mock.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
			),
		),

		'jquery.valueview.experts.staticdom' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.StaticDom.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
			),
		),

		'jquery.valueview.experts.stringvalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.StringValue.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.eachchange',
				'jquery.inputAutoExpand',
				'jquery.fn.focusAt',
			),
		),

		'jquery.valueview.experts.coordinateinput' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.CoordinateInput.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.fn.focusAt',
				'jquery.coordinate.coordinateinput',
				'jquery.ui.inputextender',
				'jquery.valueview.preview',
			),
			'messages' => array(
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		'jquery.valueview.experts.coordinatevalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.CoordinateValue.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.coordinateinput',
			),
		),

		'jquery.valueview.experts.timeinput' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.TimeInput.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.experts/experts.TimeInput.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.fn.focusAt',
				'jquery.time.timeinput',
				'jquery.ui.inputextender',
				'jquery.ui.listrotator',
				'jquery.ui.toggler',
				'jquery.valueview.preview',
			),
			'messages' => array(
				'valueview-expert-advancedadjustments',
				'valueview-expert-timeinput-precision',
				'valueview-expert-timeinput-calendar',
				'valueview-expert-timeinput-calendarhint',
				'valueview-expert-timeinput-calendarhint-switch',
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		'jquery.valueview.experts.timevalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.TimeValue.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.experts/experts.TimeValue.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.timeinput',
			),
		),

		'jquery.valueview.experts.commonsmediatype' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.SuggestedStringValue.js',
				'jquery.valueview/valueview.experts/experts.CommonsMediaType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.stringvalue',
				'jquery.ui.suggester',
			),
		),

		'jquery.valueview.preview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.preview.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.preview.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
			),
			'messages' => array(
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		)

	);

} );
// @codeCoverageIgnoreEnd
