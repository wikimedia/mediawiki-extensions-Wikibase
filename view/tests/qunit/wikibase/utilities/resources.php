<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/wikibase/utilities',
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
