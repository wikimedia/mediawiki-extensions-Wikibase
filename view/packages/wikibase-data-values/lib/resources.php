<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0]
	);

	return array(

		'globeCoordinate.js' => $moduleTemplate + array(
			'scripts' => array(
				'globeCoordinate/globeCoordinate.js',
				'globeCoordinate/globeCoordinate.Formatter.js',
				'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
			),
		),

		'qunit.parameterize' => $moduleTemplate + array(
			'scripts' => array(
				'qunit.parameterize/qunit.parameterize.js',
			),
		),

		'util.inherit' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.inherit.js',
			),
		),

	);

} );
