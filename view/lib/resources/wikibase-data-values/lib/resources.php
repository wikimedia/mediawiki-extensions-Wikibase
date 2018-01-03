<?php

/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . 'wikibase-data-values' . DIRECTORY_SEPARATOR . 'lib';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0]
	];

	return [
		'globeCoordinate.js' => $moduleTemplate + [
				'scripts' => [
					'globeCoordinate/globeCoordinate.js',
					'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
				],
			],

		'util.inherit' => $moduleTemplate + [
				'scripts' => [
					'util/util.inherit.js',
				],
			],
	];
} );
