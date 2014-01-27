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
 * - jQuery.time.timeinput
 * - jQuery.ui.toggler
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' =>  $remoteExtPathParts[1] . '/resources',
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
				'jquery.valueview.MessageProvider',
				'jquery.valueview.base',
				'dataValues.util',
				'dataValues.values',
				'dataTypes',
			),
		),

		'jquery.valueview.MessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.MessageProvider.js',
			),
			'dependencies' => array(
				'jquery.valueview.base',
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

		'jquery.valueview.experts.globecoordinateinput' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.GlobeCoordinateInput.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.fn.focusAt',
				'jquery.ui.inputextender',
				'jquery.valueview.preview',
			),
			'messages' => array(
				'valueview-expert-globecoordinateinput-precision',
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		'jquery.valueview.experts.globecoordinatevalue' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.GlobeCoordinateValue.js',
			),
			'styles' => array(
				'jquery.valueview/valueview.experts/experts.GlobeCoordinateInput.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.globecoordinateinput',
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
				'valueview-expert-timeinput-calendarhint-gregorian',
				'valueview-expert-timeinput-calendarhint-julian',
				'valueview-expert-timeinput-calendarhint-switch-gregorian',
				'valueview-expert-timeinput-calendarhint-switch-julian',
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
			'messages' => array(
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
			),
		),

		'jquery.valueview.experts.urltype' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.UrlType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.stringvalue'
			),
		),

		'jquery.valueview.experts.quantitytype' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.QuantityType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.stringvalue',
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
				'mediawiki.legacy.shared',
			),
			'messages' => array(
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		)

	);

} );
// @codeCoverageIgnoreEnd
