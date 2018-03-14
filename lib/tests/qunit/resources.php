<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/tests/qunit',
	];

	$modules = [
		'wikibase.tests.qunit.testrunner' => $moduleBase + [
			'scripts' => 'data/testrunner.js',
			'dependencies' => [
				'test.mediawiki.qunit.testrunner',
				'wikibase',
			],
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
