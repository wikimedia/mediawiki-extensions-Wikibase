<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(

		'jquery.valueview.experts.CommonsMediaType' => $moduleTemplate + array(
			'scripts' => array(
				'CommonsMediaType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.valueview.experts.SuggestedStringValue',
				'jquery.valueview.Expert',
			),
		),

		'jquery.valueview.experts.EmptyValue' => $moduleTemplate + array(
			'scripts' => array(
				'EmptyValue.js',
			),
			'styles' => array(
				'EmptyValue.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			),
			'messages' => array(
				'valueview-expert-emptyvalue-empty',
			),
		),

		'jquery.valueview.experts.GlobeCoordinateInput' => $moduleTemplate + array(
			'scripts' => array(
				'GlobeCoordinateInput.js',
			),
			'styles' => array(
				'GlobeCoordinateInput.css',
			),
			'dependencies' => array(
				'globeCoordinate.js',
				'jquery',
				'jquery.ui.inputextender',
				'jquery.ui.preview',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			),
			'messages' => array(
				'valueview-expert-globecoordinateinput-precision',
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		'jquery.valueview.experts.StringValue' => $moduleTemplate + array(
			'scripts' => array(
				'StringValue.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.event.special.eachchange',
				'jquery.focusAt',
				'jquery.inputautoexpand',
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			),
		),

		'jquery.valueview.experts.SuggestedStringValue' => $moduleTemplate + array(
			'scripts' => array(
				'SuggestedStringValue.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.suggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			),
		),

		'jquery.valueview.experts.TimeInput' => $moduleTemplate + array(
			'scripts' => array(
				'TimeInput.js',
			),
			'styles' => array(
				'TimeInput.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.inputextender',
				'jquery.ui.listrotator',
				'jquery.ui.preview',
				'jquery.ui.toggler',
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
				'time.js',
				'util.MessageProvider',
			),
			'messages' => array(
				'valueview-expert-advancedadjustments',
				'valueview-expert-timeinput-calendar',
				'valueview-expert-timeinput-calendarhint-gregorian',
				'valueview-expert-timeinput-calendarhint-julian',
				'valueview-expert-timeinput-calendarhint-switch-gregorian',
				'valueview-expert-timeinput-calendarhint-switch-julian',
				'valueview-expert-timeinput-precision',
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
				'valueview-preview-label',
				'valueview-preview-novalue',
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
			),
		),

		'jquery.valueview.experts.UnsupportedValue' => $moduleTemplate + array(
			'scripts' => array(
				'UnsupportedValue.js',
			),
			'styles' => array(
				'UnsupportedValue.css',
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			),
			'messages' => array(
				'valueview-expert-unsupportedvalue-unsupporteddatatype',
				'valueview-expert-unsupportedvalue-unsupporteddatavalue',
			)
		),
	);

} );
