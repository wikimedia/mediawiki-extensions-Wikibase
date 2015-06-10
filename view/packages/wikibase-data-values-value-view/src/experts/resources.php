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
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
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
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.Container',
				'jquery.valueview.ExpertExtender.Listrotator',
				'jquery.valueview.ExpertExtender.Preview',
				'jquery.valueview.ExpertExtender.Toggler',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			),
			'messages' => array(
				'valueview-expert-globecoordinateinput-precision',
				'valueview-expert-globecoordinateinput-customprecision',
			),
		),

		'jquery.valueview.experts.MonolingualText' => $moduleTemplate + array(
			'scripts' => array(
				'MonolingualText.js',
			),
			'dependencies' => array(
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.LanguageSelector',
				'jquery.valueview.experts',
				'jquery.valueview.experts.StringValue',
			),
		),

		'jquery.valueview.experts.StringValue' => $moduleTemplate + array(
			'scripts' => array(
				'StringValue.js',
			),
			'dependencies' => array(
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
				'dataValues.TimeValue',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.ExpertExtender.CalendarHint',
				'jquery.valueview.ExpertExtender.Container',
				'jquery.valueview.ExpertExtender.Listrotator',
				'jquery.valueview.ExpertExtender.Preview',
				'jquery.valueview.ExpertExtender.Toggler',
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			),
			'messages' => array(
				'valueview-expert-timeinput-calendar',
				'valueview-expert-timeinput-precision',
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
			),
		),

		'jquery.valueview.experts.UnDeserializableValue' => $moduleTemplate + array(
			'scripts' => array(
				'UnDeserializableValue.js'
			),
			'dependencies' => array(
				'jquery.valueview.experts',
				'jquery.valueview.Expert',
			)
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
