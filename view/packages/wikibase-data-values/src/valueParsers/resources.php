<?php
/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/src/valueParsers',
		'remoteExtPath' => $remoteExtPathParts[1],
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
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers',
			),
		),

		'valueParsers.ValueParserFactory' => $moduleTemplate + array(
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
				'parsers/FloatParser.js',
				'parsers/IntParser.js',
				'parsers/NullParser.js',
				'parsers/StringParser.js',
				'parsers/TimeParser.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers.ValueParser',
				'time.js', // required by TimeParser
			),
		),

	);

} );
