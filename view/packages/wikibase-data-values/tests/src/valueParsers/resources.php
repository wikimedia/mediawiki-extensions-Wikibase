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

		'valueParsers.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ValueParser.tests.js',
			),
			'dependencies' => array(
				'valueParsers.parsers',
			),
		),

		'valueParsers.factory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'ValueParserFactory.tests.js',
			),
			'dependencies' => array(
				'qunit.parameterize',
				'valueParsers.factory',
				'valueParsers.parsers',
			),
		),

		'valueParsers.parsers.tests' => $moduleTemplate + array(
			'scripts' => array(
				'parsers/BoolParser.tests.js',
				'parsers/GlobeCoordinateParser.tests.js',
				'parsers/FloatParser.tests.js',
				'parsers/IntParser.tests.js',
				'parsers/StringParser.tests.js',
				'parsers/TimeParser.tests.js',
				'parsers/QuantityParser.tests.js',
				'parsers/NullParser.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers.tests',
			),
		),

	);

} );
