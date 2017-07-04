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
		'wikibase.formatters.ApiValueFormatter' => $moduleTemplate + [
			'scripts' => [
				'ApiValueFormatter.js',
			],
			'dependencies' => [
				'wikibase',
				'util.inherit',
				'valueFormatters.ValueFormatter',
			],
		],

		'wikibase.formatters.ApiValueFormatterFactory' => $moduleTemplate + [
			'scripts' => [
				'ApiValueFormatterFactory.js',
			],
			'dependencies' => [
				'wikibase.api.FormatValueCaller',
				'wikibase.formatters.ApiValueFormatter',
				'wikibase.ValueFormatterFactory'
			],
		],
	];
} );
