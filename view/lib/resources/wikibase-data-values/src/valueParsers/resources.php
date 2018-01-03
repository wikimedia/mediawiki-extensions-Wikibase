<?php
/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'wikibase-data-values'
		. DIRECTORY_SEPARATOR . 'src' .  DIRECTORY_SEPARATOR . 'valueParsers';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'valueParsers' => $moduleTemplate + [
				'scripts' => [
					'valueParsers.js',
				],
			],

		'valueParsers.ValueParser' => $moduleTemplate + [
				'scripts' => [
					'parsers/ValueParser.js',
				],
				'dependencies' => [
					'util.inherit',
					'valueParsers',
				],
			],

		'valueParsers.ValueParserStore' => $moduleTemplate + [
				'scripts' => [
					'ValueParserStore.js',
				],
				'dependencies' => [
					'valueParsers',
				],
			],

		'valueParsers.parsers' => $moduleTemplate + [
				'scripts' => [
					'parsers/BoolParser.js',
					'parsers/FloatParser.js',
					'parsers/IntParser.js',
					'parsers/NullParser.js',
					'parsers/StringParser.js',
				],
				'dependencies' => [
					'dataValues.values',
					'util.inherit',
					'valueParsers.ValueParser',
				],
			],
	];
} );
