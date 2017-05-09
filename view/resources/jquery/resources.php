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

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'jquery.removeClassByRegex' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.removeClassByRegex.js',
			),
		),

		'jquery.sticknode' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.sticknode.js',
			),
			'dependencies' => array(
				'jquery.util.EventSingletonManager',
			),
		),

		'jquery.util.EventSingletonManager' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util.EventSingletonManager.js',
			),
			'dependencies' => array(
				'jquery.throttle-debounce',
			),
		),

	);

	return $modules;
} );
