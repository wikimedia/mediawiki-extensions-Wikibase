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
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'wikibase.store.ApiEntityStore' => $moduleTemplate + array(
			'scripts' => array(
				'store.ApiEntityStore.js',
			),
			'dependencies' => array(
				'wikibase.store',
				'wikibase.store.EntityStore',
			),
		),

		'wikibase.store.CombiningEntityStore' => $moduleTemplate + array(
			'scripts' => array(
				'store.CombiningEntityStore.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store',
				'wikibase.store.EntityStore',
			),
		),

		'wikibase.store.EntityStore' => $moduleTemplate + array(
			'scripts' => array(
				'store.EntityStore.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.store',
			),
		),

		'wikibase.store.FetchedContent' => $moduleTemplate + array(
			'scripts' => array(
				'store.FetchedContent.js',
			),
			'dependencies' => array(
				'mediawiki.Title',
				'wikibase.store',
			),
		),

		'wikibase.store.FetchedContentUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'store.FetchedContentUnserializer.js',
			),
			'dependencies' => array(
				'mediawiki.Title',
				'util.inherit',
				'wikibase.serialization.Deserializer',
				'wikibase.store',
				'wikibase.store.FetchedContent',
			),
		),

		'wikibase.store' => $moduleTemplate + array(
			'scripts' => array(
				'store.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),

		'wikibase.store.MwConfigEntityStore' => $moduleTemplate + array(
			'scripts' => array(
				'store.MwConfigEntityStore.js',
			),
			'dependencies' => array(
				'json',
				'wikibase.store',
				'wikibase.store.EntityStore',
				'wikibase.store.FetchedContent',
			),
		),

	);

	return $modules;
} );
