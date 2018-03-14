<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/wikibase/store',
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
