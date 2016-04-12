<?php
/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(
		'jquery.valueview.ExpertExtender' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.js',
			),
			'dependencies' => array(
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
				'dataValues.TimeValue',
				'jquery.valueview.ExpertExtender',
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
				'jquery.valueview.ExpertExtender',
			),
		),

		'jquery.valueview.ExpertExtender.LanguageSelector' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.LanguageSelector.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.languagesuggester',
				'jquery.valueview.ExpertExtender',
				'util.PrefixingMessageProvider',
			),
			'messages' => array(
				'valueview-expertextender-languageselector-languagetemplate',
				'valueview-expertextender-languageselector-label',
			)
		),

		'jquery.valueview.ExpertExtender.Listrotator' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Listrotator.js',
			),
			'dependencies' => array(
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
				'jquery.ui.preview',
				'jquery.valueview.ExpertExtender',
				'util.PrefixingMessageProvider',
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
				'jquery.ui.toggler',
				'jquery.valueview.ExpertExtender',
			),
			'messages' => array(
				'valueview-expert-advancedadjustments',
			),
		),

		'jquery.valueview.ExpertExtender.UnitSelector' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.UnitSelector.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender',
				'jquery.ui.unitsuggester',
			),
			'messages' => array(
				'valueview-expertextender-unitsuggester-label',
			),
		),
	);

	return $modules;
} );
