<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	$modules = [

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

	return $modules;

} );
