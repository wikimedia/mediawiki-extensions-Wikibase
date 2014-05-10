<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
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
		'jquery.valueview.ExpertExtender' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.inputextender',
				'jquery.valueview',
				'util.Extendable',
			),
		),

		'jquery.valueview.ExpertExtender.CalendarHint' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.CalendarHint.js',
			),
			'styles' => array(
				'ExpertExtender.CalendarHint.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.valueview.ExpertExtender',
				'time.js'
			),
			'messages' => array(
				'valueview-expertextender-calendarhint-gregorian',
				'valueview-expertextender-calendarhint-julian',
				'valueview-expertextender-calendarhint-switch-gregorian',
				'valueview-expertextender-calendarhint-switch-julian'
			)
		),

		'jquery.valueview.ExpertExtender.Container' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Container.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.valueview.ExpertExtender',
			),
		),

		'jquery.valueview.ExpertExtender.Listrotator' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Listrotator.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.listrotator',
				'jquery.valueview.ExpertExtender',
			),
		),

		'jquery.valueview.ExpertExtender.Preview' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Preview.js',
			),
			'styles' => array(
				'ExpertExtender.Preview.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.preview',
				'jquery.valueview.ExpertExtender',
				'util.MessageProvider',
			),
			'messages' => array(
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		'jquery.valueview.ExpertExtender.Toggler' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Toggler.js',
			),
			'styles' => array(
				'ExpertExtender.Toggler.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.toggler',
				'jquery.valueview.ExpertExtender',
			),
			'messages' => array(
				'valueview-expert-advancedadjustments',
			),
		),
	);
} );
