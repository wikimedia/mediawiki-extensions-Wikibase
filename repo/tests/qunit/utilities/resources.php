<?php

/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'wikibase.utilities.ClaimGuidGenerator.tests' => $moduleBase + array(
			'scripts' => array(
				'ClaimGuidGenerator.tests.js',
			),
			'dependencies' => array(
				'wikibase.utilities.ClaimGuidGenerator',
			),
		),

		'wikibase.utilities.GuidGenerator.tests' => $moduleBase + array(
			'scripts' => array(
				'GuidGenerator.tests.js',
			),
			'dependencies' => array(
				'wikibase.utilities.GuidGenerator',
			),
		),
	);

	return $modules;
} );
