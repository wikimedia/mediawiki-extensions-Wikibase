<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
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

		'jquery.valueview.ExpertExtender.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender'
			),
		),

		'jquery.valueview.ExpertExtender.CalendarHint.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.CalendarHint.tests.js',
			),
			'dependencies' => array(
				'dataValues.TimeValue',
				'jquery.valueview.ExpertExtender.CalendarHint',
				'jquery.valueview.test.testExpertExtenderExtension',
				'util.HashMessageProvider',
			),
		),

		'jquery.valueview.ExpertExtender.Container.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Container.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.Container',
				'jquery.valueview.test.testExpertExtenderExtension'
			),
		),

		'jquery.valueview.ExpertExtender.LanguageSelector.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.LanguageSelector.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.LanguageSelector',
				'jquery.valueview.test.testExpertExtenderExtension'
			),
		),

		'jquery.valueview.ExpertExtender.UnitSelector.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.UnitSelector.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.UnitSelector',
				'jquery.valueview.test.testExpertExtenderExtension'
			),
		),

		'jquery.valueview.ExpertExtender.Listrotator.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Listrotator.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.Listrotator',
				'jquery.valueview.test.testExpertExtenderExtension'
			),
		),

		'jquery.valueview.ExpertExtender.Preview.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Preview.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.Preview',
				'jquery.valueview.test.testExpertExtenderExtension'
			),
		),

		'jquery.valueview.ExpertExtender.Toggler.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ExpertExtender.Toggler.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertExtender.Toggler',
				'jquery.valueview.test.testExpertExtenderExtension',
				'util.HashMessageProvider'
			),
		),

		'jquery.valueview.test.testExpertExtenderExtension' => $moduleTemplate + array(
			'scripts' => array(
				'testExpertExtenderExtension.js',
			)
		),
	);

} );
