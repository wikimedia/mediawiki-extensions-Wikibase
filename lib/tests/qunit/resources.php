<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
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
		'wikibase.tests.qunit.testrunner' => $moduleBase + [
			'scripts' => 'data/testrunner.js',
			'dependencies' => [
				'test.mediawiki.qunit.testrunner',
				'wikibase',
			],
			'position' => 'top'
		],

		'wikibase.Site.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.Site.tests.js',
			],
			'dependencies' => [
				'wikibase.Site',
			],
		],

		'wikibase.sites.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.sites.tests.js',
			],
			'dependencies' => [
				'wikibase',
				'wikibase.Site',
				'wikibase.sites',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'wikibase.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.tests.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],
	];

	return array_merge(
		$modules,
		include __DIR__ . '/jquery.wikibase/resources.php'
	);
} );
