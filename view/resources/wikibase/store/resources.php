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
