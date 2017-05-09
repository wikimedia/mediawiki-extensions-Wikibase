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
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(
		'wikibase.store.CachingEntityStore.tests' => $moduleBase + array(
			'scripts' => array(
				'store.CachingEntityStore.tests.js',
			),
			'dependencies' => array(
				'wikibase.store.CachingEntityStore',
				'wikibase.store.EntityStore',
			),
		),

		'wikibase.store.CombiningEntityStore.tests' => $moduleBase + array(
			'scripts' => array(
				'store.CombiningEntityStore.tests.js',
			),
			'dependencies' => array(
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.EntityStore',
			),
		),
	);
} );
