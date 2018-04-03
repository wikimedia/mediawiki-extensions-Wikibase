<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/jquery',
	];

	$modules = [

		'jquery.removeClassByRegex' => $moduleTemplate + [
			'scripts' => [
				'jquery.removeClassByRegex.js',
			],
		],

		'jquery.sticknode' => $moduleTemplate + [
			'scripts' => [
				'jquery.sticknode.js',
			],
			'dependencies' => [
				'jquery.util.EventSingletonManager',
			],
		],

		'jquery.util.EventSingletonManager' => $moduleTemplate + [
			'scripts' => [
				'jquery.util.EventSingletonManager.js',
			],
			'dependencies' => [
				'jquery.throttle-debounce',
			],
		],

	];

	return $modules;
} );
