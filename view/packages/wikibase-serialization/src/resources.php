<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match(
		'+^(.*?)' . preg_quote( DIRECTORY_SEPARATOR ) . '(vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '(.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '../' . $remoteExtPathParts[2]
			. DIRECTORY_SEPARATOR . $remoteExtPathParts[3],
	);

	$modules = array(

		'wikibase.serialization' => $moduleTemplate + array(
			'scripts' => array(
				'init.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimUnserializer',
				'wikibase.serialization.EntityUnserializer',
				'wikibase.serialization.ReferenceUnserializer',
				'wikibase.serialization.SerializerFactory',
				'wikibase.serialization.SnakListUnserializer',
			),
		),

		'wikibase.serialization.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),

		'wikibase.serialization.SerializerFactory' => $moduleTemplate + array(
			'scripts' => array(
				'SerializerFactory.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.Unserializer',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/Serializers/resources.php' ),
		include( __DIR__ . '/Unserializers/resources.php' )
	);
} );
