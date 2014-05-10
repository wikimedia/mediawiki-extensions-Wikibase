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

	$resources = array(

		'jquery.valueview.ExpertStore.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ExpertStore.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.tests.MockExpert',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.valueview.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.valueview.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.qunit.completenessTest',
				'jquery.valueview.valueview',
				'test.sinonjs',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterStore',
				'valueParsers.parsers',
				'valueParsers.ValueParserStore'
			),
		),

		'jquery.valueview.tests.MockExpert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.tests.MockExpert.js',
			),
			'dependencies' => array(
				'jquery.valueview.Expert',
			),
		),

		'jquery.valueview.tests.MockViewState' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.tests.MockViewState.js',
			),
			'dependencies' => array(
				'jquery.valueview.ViewState',
				'util.inherit',
			),
		),

		'jquery.valueview.tests.MockViewState.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.tests.MockViewState.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.tests.MockViewState',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.tests.testExpert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.tests.testExpert.js',
			),
			'dependencies' => array(
				'jquery.valueview.Expert',
				'jquery.valueview.tests.MockViewState',
				'qunit.parameterize',
				'util.Notifier',
			),
		),

	);

	return ( $resources + include( __DIR__ . '/experts/resources.php' ) ) +
		include( __DIR__ . '/ExpertExtender/resources.php' );

} );
