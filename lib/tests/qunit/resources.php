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
	$packageFilesModuleBase = [
		'localBasePath' => dirname( dirname( __DIR__ ) ),
		'remoteExtPath' => 'Wikibase/repo/',
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

		'wikibase.tests.jquery.ui.suggester' => $moduleBase + [
			'scripts' => [
				'lib/jquery.ui/jquery.ui.ooMenu.tests.js',
				'lib/jquery.ui/jquery.ui.suggester.tests.js',
			],
			'dependencies' => [
				'jquery.ui.suggester',
			],
		],

		'wikibase.tests.jquery.util.getscrollbarwidth' => $packageFilesModuleBase + [
			'packageFiles' => [
				'tests/qunit/lib/jquery.util/jquery.util.getscrollbarwidth.tests.js',

				'resources/lib/jquery.util/jquery.util.getscrollbarwidth.js'
			],
		],
	];

	return array_merge(
		$modules,
		require __DIR__ . '/jquery.wikibase/resources.php'
	);
} );
