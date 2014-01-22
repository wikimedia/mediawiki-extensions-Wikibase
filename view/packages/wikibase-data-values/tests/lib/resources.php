<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
	);

	return array(

		'util.inherit.tests' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.inherit.tests.js',
			),
			'dependencies' => array(
				'jquery',
				'util.inherit',
			),
		),

		'globeCoordinate.js.tests' => $moduleTemplate + array(
			'scripts' => array(
				'globeCoordinate/globeCoordinate.tests.js',
				'globeCoordinate/globeCoordinate.Formatter.tests.js',
				'globeCoordinate/globeCoordinate.GlobeCoordinate.tests.js',
			),
			'dependencies' => array(
				'globeCoordinate.js',
			),
		),

		'time.js.tests' => $moduleTemplate + array(
			'scripts' => array(
				'time/time.Time.knowsPrecision.tests.js',
				'time/time.Time.minPrecision.tests.js',
				'time/time.Time.maxPrecision.tests.js',
				'time/time.Time.tests.js',
				'time/time.Time.validate.tests.js',
				'time/time.Parser.tests.js',
				'time/time.Time.newFromIso8601.tests.js',
			),
			'dependencies' => array(
				'time.js',
				'time.js.validTimeDefinitions',
			),
		),

	);

} );
