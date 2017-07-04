<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	$modules = [

		'wikibase.utilities.ClaimGuidGenerator.tests' => $moduleBase + [
			'scripts' => [
				'ClaimGuidGenerator.tests.js',
			],
			'dependencies' => [
				'wikibase.utilities.ClaimGuidGenerator',
			],
		],

		'wikibase.utilities.GuidGenerator.tests' => $moduleBase + [
			'scripts' => [
				'GuidGenerator.tests.js',
			],
			'dependencies' => [
				'wikibase.utilities.GuidGenerator',
			],
		],
	];

	return $modules;
} );
