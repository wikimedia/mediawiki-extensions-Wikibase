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

	$resources = array(

		'jquery.wikibase.snakview.tests' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.tests.js',
			),
			'dependencies' => array(
				'dataTypes.DataTypeStore',
				'jquery.wikibase.snakview',
				'mediawiki.Title',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
				'wikibase.store.FetchedContent',
			),
		),

	);

	return $resources;
} );
