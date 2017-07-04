<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
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
