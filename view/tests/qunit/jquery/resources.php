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
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
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
