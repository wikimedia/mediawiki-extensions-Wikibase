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

		'jquery.valueview.experts.GlobeCoordinateInput.tests' => $moduleTemplate + array(
			'scripts' => array(
				'GlobeCoordinateInput.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.GlobeCoordinateInput',
				'jquery.valueview.tests.testExpert',
			),
		),

		'jquery.valueview.experts.StringValue.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StringValue.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.tests.testExpert',
			),
		),

		'jquery.valueview.experts.TimeInput.tests' => $moduleTemplate + array(
			'scripts' => array(
				'TimeInput.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.TimeInput',
				'jquery.valueview.tests.testExpert',
				'time.js',
			),
		),

	);

} );
