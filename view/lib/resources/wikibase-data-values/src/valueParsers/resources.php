<?php
/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../../wikibase-data-values/src/valueParsers',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src/valueParsers',
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
