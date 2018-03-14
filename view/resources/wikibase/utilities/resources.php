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
		'remoteExtPath' => 'Wikibase/view/resources/wikibase/utilities',
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
