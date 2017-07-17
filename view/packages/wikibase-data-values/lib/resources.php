<?php
/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0]
	];

	return [
		'globeCoordinate.js' => $moduleTemplate + [
			'scripts' => [
				'globeCoordinate/globeCoordinate.js',
				'globeCoordinate/globeCoordinate.Formatter.js',
				'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			],
		],

		'qunit.parameterize' => $moduleTemplate + [
			'scripts' => [
				'qunit.parameterize/qunit.parameterize.js',
			],
		],

		'util.inherit' => $moduleTemplate + [
			'scripts' => [
				'util/util.inherit.js',
			],
		],
	];
} );
