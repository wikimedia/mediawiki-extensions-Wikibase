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

	$modules = [

		'wikibase.utilities.ClaimGuidGenerator' => $moduleTemplate + [
			'scripts' => [
				'wikibase.utilities.ClaimGuidGenerator.js',
			],
			'dependencies' => [
				'wikibase.utilities.GuidGenerator',
			],
		],

		'wikibase.utilities.GuidGenerator' => $moduleTemplate + [
			'scripts' => [
				'wikibase.utilities.GuidGenerator.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.utilities',
			],
		],

		'wikibase.utilities' => $moduleTemplate + [
			'scripts' => [
				'wikibase.utilities.js',
				'wikibase.utilities.ui.js',
			],
			'styles' => [
				'wikibase.utilities.ui.css',
			],
			'dependencies' => [
				'wikibase',
				'mediawiki.language',
				'mediawiki.jqueryMsg'
			],
		],

	];

	return $modules;
} );
