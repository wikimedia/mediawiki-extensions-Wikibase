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

		'wikibase.store.EntityStore' => $moduleTemplate + array(
			'scripts' => array(
				'store.EntityStore.js',
			),
			'dependencies' => array(
				'mediawiki.Title',
				'wikibase.store',
				'wikibase.store.FetchedContent',
			),
		),

		'wikibase.store.FetchedContent' => $moduleTemplate + array(
			'scripts' => array(
				'store.FetchedContent.js',
			),
			'dependencies' => array(
				'wikibase.store',
				'mediawiki.Title',
			),
		),

		'wikibase.store.FetchedContentUnserializer' => $moduleTemplate + array(
			'scripts' => array(
				'store.FetchedContentUnserializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization', // For registering in the SerializerFactory
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

	);

	return $modules;
} );
