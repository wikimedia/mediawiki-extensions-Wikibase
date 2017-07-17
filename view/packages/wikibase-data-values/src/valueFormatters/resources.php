<?php
/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'valueFormatters' => $moduleTemplate + [
			'scripts' => [
				'valueFormatters.js',
			],
		],

		'valueFormatters.ValueFormatter' => $moduleTemplate + [
			'scripts' => [
				'formatters/ValueFormatter.js',
			],
			'dependencies' => [
				'util.inherit',
				'valueFormatters',
			],
		],

		'valueFormatters.ValueFormatterStore' => $moduleTemplate + [
			'scripts' => [
				'ValueFormatterStore.js',
			],
			'dependencies' => [
				'valueFormatters',
			],
		],

		'valueFormatters.formatters' => $moduleTemplate + [
			'scripts' => [
				'formatters/NullFormatter.js',
				'formatters/StringFormatter.js',
			],
			'dependencies' => [
				'dataValues.values',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			],
		],
	];
} );
