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
		'remoteExtPath' => 'Wikibase/lib/tests/qunit/jquery.wikibase',
	];

	return [
		'jquery.wikibase.siteselector.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.siteselector.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.siteselector',
				'wikibase.Site',
			],
		],

		'jquery.wikibase.wbtooltip.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.wbtooltip.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.wbtooltip',
			],
		],
	];
} );
