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
		'remoteExtPath' => 'Wikibase/view/tests/qunit/jquery',
	];

	return [
		'jquery.removeClassByRegex.tests' => $moduleBase + [
			'scripts' => [
				'jquery.removeClassByRegex.tests.js',
			],
			'dependencies' => [
				'jquery.removeClassByRegex',
			],
		],

		'jquery.sticknode.tests' => $moduleBase + [
			'scripts' => [
				'jquery.sticknode.tests.js',
			],
			'dependencies' => [
				'jquery.sticknode',
			],
		],

		'jquery.util.EventSingletonManager.tests' => $moduleBase + [
			'scripts' => [
				'jquery.util.EventSingletonManager.tests.js',
			],
			'dependencies' => [
				'jquery.util.EventSingletonManager',
			],
		],

		'jquery.util.getDirectionality.tests' => $moduleBase + [
			'scripts' => [
				'jquery.util.getDirectionality.tests.js',
			],
			'dependencies' => [
				'jquery.util.getDirectionality',
			],
		],
	];
} );
