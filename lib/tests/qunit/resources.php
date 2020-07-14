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
		'wikibase.Site.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.Site.tests.js',
			],
			'dependencies' => [
				'wikibase.Site',
			],
		],
	];

	return $modules;
} );
