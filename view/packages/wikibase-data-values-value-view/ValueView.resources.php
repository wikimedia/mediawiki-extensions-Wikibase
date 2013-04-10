<?php
/**
 * Definition of "ValueView" resourceloader modules.
 * When included this returns an array with all modules introduced by the "valueview" jQuery
 * extension.
 *
 * External dependencies:
 * - jQuery 1.8
 * - jQuery.eachchange (maintained by wikidata team)
 * - jQuery.inputAutoExpand (maintained by wikidata team)
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
				'jquery.valueview.experts', // because vv deals with ExpertFactory
				'jquery.valueview.experts.unsupportedvalue', // for displaying unsupported values
				'jquery.valueview.experts.emptyvalue', // for displaying empty values
				'jquery.valueview.experts.stringvalue',
				'jquery.valueview.experts.entityidvalue',
				'jquery.valueview.experts.commonsmediatype',
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

		'jquery.valueview.experts.entityidvalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.BifidExpert.js', // todo: define separate modules
				'jquery.valueview/valueview.experts/experts.StaticDom.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.stringvalue',
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
			),
		),

		'jquery.valueview.experts.commonsmediatype' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.BifidExpert.js', // todo: define separate modules
				'jquery.valueview/valueview.experts/experts.StaticDom.js',
				'jquery.valueview/valueview.experts/experts.SuggestedStringValue.js',
				'jquery.valueview/valueview.experts/experts.CommonsMediaType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.stringvalue',
				'jquery.ui.suggester',
			),
		),
	);

} );
// @codeCoverageIgnoreEnd
