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
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(
		'jquery.removeClassByRegex.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.removeClassByRegex.tests.js',
			),
			'dependencies' => array(
				'jquery.removeClassByRegex',
			),
		),

		'jquery.sticknode.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.sticknode.tests.js',
			),
			'dependencies' => array(
				'jquery.sticknode',
			),
		),

		'jquery.util.EventSingletonManager.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.util.EventSingletonManager.tests.js',
			),
			'dependencies' => array(
				'jquery.util.EventSingletonManager',
			),
		),

		'jquery.util.getDirectionality.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.util.getDirectionality.tests.js',
			),
			'dependencies' => array(
				'jquery.util.getDirectionality',
			),
		),
	);
} );
