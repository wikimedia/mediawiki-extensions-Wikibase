<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [
		'wikibase.store.CachingEntityStore.tests' => $moduleBase + [
			'scripts' => [
				'store.CachingEntityStore.tests.js',
			],
			'dependencies' => [
				'wikibase.store.CachingEntityStore',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.CombiningEntityStore.tests' => $moduleBase + [
			'scripts' => [
				'store.CombiningEntityStore.tests.js',
			],
			'dependencies' => [
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.EntityStore',
			],
		],
	];
} );
