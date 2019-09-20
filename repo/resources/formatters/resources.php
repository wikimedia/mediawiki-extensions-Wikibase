<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/repo/resources/formatters',
	];

	return [
		'wikibase.formatters.ApiValueFormatter' => $moduleTemplate + [
			'scripts' => [
				'ApiValueFormatter.js',
			],
			'dependencies' => [
				'wikibase',
				'util.inherit',
				'valueFormatters',
			],
		],
	];
} );
