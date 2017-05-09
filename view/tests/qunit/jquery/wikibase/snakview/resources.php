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
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
			),
		),

	);

	return $resources;
} );
