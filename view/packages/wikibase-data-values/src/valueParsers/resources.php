<?php
/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match(
		'+^.*?' . preg_quote( DIRECTORY_SEPARATOR, '+' ) . '((?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR, '+' ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . DIRECTORY_SEPARATOR . $remoteExtPathParts[1],
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

		'valueParsers.ValueParserStore' => $moduleTemplate + array(
			'scripts' => array(
				'ValueParserStore.js',
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
				'dataValues.values',
				'time.js', // required by TimeParser
				'util.inherit',
				'valueParsers.ValueParser',
			),
		),

	);

} );
