<?php
/**
 * Definition of ResourceLoader modules of the ValueParsers extension.
 * When included this returns an array with all the modules introduced by ValueParsers.
 *
 * @since 0.1
 *
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/src/ValueParsers',
		'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ) . '/src/ValueParsers',
	);

	return array(
		'valueParsers' => $moduleTemplate + array(
			'scripts' => array(
				'valueParsers.js',
			),
		),

		'valueParsers.ValueParser' => $moduleTemplate + array(
			'scripts' => array(
				'parsers/ValueParser.js',
				'parsers/ApiBasedValueParser.js',
			),
			'dependencies' => array(
				'valueParsers',
				'valueParsers.util',
			),
		),

		'valueParsers.factory' => $moduleTemplate + array(
			'scripts' => array(
				'ValueParserFactory.js',
			),
			'dependencies' => array(
				'valueParsers',
			),
		),

		'valueParsers.parsers' => $moduleTemplate + array(
			'scripts' => array(
				'parsers/BoolParser.js',
				'parsers/GlobeCoordinateParser.js',
				'parsers/FloatParser.js',
				'parsers/IntParser.js',
				'parsers/StringParser.js',
				'parsers/TimeParser.js',
				'parsers/QuantityParser.js',
				'parsers/NullParser.js',
			),
			'dependencies' => array(
				'valueParsers.ValueParser',
				'valueParsers.api',
				'globeCoordinate.js', // required by GlobeCoordinateParser
				'time.js', // required by TimeParser
			),
		),

		'valueParsers.util' => $moduleTemplate + array(
			'scripts' => array(
				'valueParsers.util.js',
			),
			'dependencies' => array(
				'dataValues.util',
				'valueParsers',
			),
		),

		'valueParsers.api' => $moduleTemplate + array(
			'scripts' => array(
				'valueParsers.Api.js',
			),
			'dependencies' => array(
				'valueParsers',
				'dataValues.values',
				'jquery.json',
			),
		),
	);

} );
// @codeCoverageIgnoreEnd
