<?php

/**
 * @licence GNU GPL v2+
 * @author Jonas Kress
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

	return array(
		'wikibase.performance.__namespace' => $moduleTemplate + array(
			'scripts' => array(
					'__namespace.js'
			),
			'dependencies' => array(
					'wikibase',
			)
		),

		'wikibase.performance.Mark' => $moduleTemplate + array(
			'scripts' => array(
				'Mark.js',
			),
			'dependencies' => array(
				'wikibase.performance.__namespace',
			),
		),
		'wikibase.performance.Statistics' => $moduleTemplate + array(
			'scripts' => array(
					'Statistics.js',
			),
			'dependencies' => array(
					'wikibase.performance.__namespace',
			),
		),

	);
} );
