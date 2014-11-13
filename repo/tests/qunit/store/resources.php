<?php

/**
 * @licence GNU GPL v2+
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

	$modules = array(

		'wikibase.store.CombiningEntityStore.tests' => $moduleBase + array(
			'scripts' => array(
				'store.CombiningEntityStore.tests.js',
			),
			'dependencies' => array(
				'wikibase.store.CombiningEntityStore',
				'wikibase.store.EntityStore',
			),
		),

		'wikibase.store.MwConfigEntityStore.tests' => $moduleBase + array(
			'scripts' => array(
				'store.MwConfigEntityStore.tests.js',
			),
			'dependencies' => array(
				'wikibase.store.MwConfigEntityStore',
			),
		),

	);

	return $modules;

} );
