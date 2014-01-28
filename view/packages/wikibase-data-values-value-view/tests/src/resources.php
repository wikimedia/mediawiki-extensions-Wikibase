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

		'jquery.valueview.ExpertFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ExpertFactory.tests.js',
			),
			'dependencies' => array(
				'dataTypes',
				'dataValues.values',
				'jquery.valueview.ExpertFactory',
				'jquery.valueview.experts.Mock',
				'qunit.parameterize',
			),
		),

		'jquery.valueview.MessageProvider.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.MessageProvider.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.MessageProvider',
			),
		),

		'jquery.valueview.preview.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.preview.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.preview',
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

	return $resources + include( __DIR__ . '/experts/resources.php' );

} );
