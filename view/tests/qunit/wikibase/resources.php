<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/wikibase',
	];

	return [
		'wikibase.getLanguageNameByCode.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.getLanguageNameByCode.tests.js'
			],
			'dependencies' => [
				'wikibase.getLanguageNameByCode'
			]
		],

		'wikibase.templates.tests' => $moduleBase + [
			'scripts' => [
				'templates.tests.js',
			],
			'dependencies' => [
				'wikibase.templates',
			],
		],

		'wikibase.ValueViewBuilder.tests' => $moduleBase + [
			'scripts' => [
				'wikibase.ValueViewBuilder.tests.js'
			],
			'dependencies' => [
				'test.sinonjs',
				'wikibase.ValueViewBuilder'
			]
		],
	];
} );
