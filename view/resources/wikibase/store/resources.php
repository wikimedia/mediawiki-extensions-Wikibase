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
		'remoteExtPath' => 'Wikibase/view/resources/wikibase/store',
	];

	$modules = [

		'wikibase.store.ApiEntityStore' => $moduleTemplate + [
			'scripts' => [
				'store.ApiEntityStore.js',
			],
			'dependencies' => [
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.CachingEntityStore' => $moduleTemplate + [
			'scripts' => [
				'store.CachingEntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.CombiningEntityStore' => $moduleTemplate + [
			'scripts' => [
				'store.CombiningEntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.EntityStore' => $moduleTemplate + [
			'scripts' => [
				'store.EntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
			],
		],

		'wikibase.store' => $moduleTemplate + [
			'scripts' => [
				'store.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

	];

	return $modules;
} );
