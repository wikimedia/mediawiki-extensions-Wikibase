<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgHooks']['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
	preg_match(
		'+^(.*?)' . preg_quote( DIRECTORY_SEPARATOR ) . '(vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '(.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'tests',
		'remoteExtPath' => '../' . $remoteExtPathParts[2]
			. DIRECTORY_SEPARATOR . $remoteExtPathParts[3]
			. DIRECTORY_SEPARATOR . 'tests',
	);

	$modules = array(

		'wikibase.serialization.ClaimsSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimsSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimsSerializer',
			),
		),

		'wikibase.serialization.ClaimsUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimsUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimsUnserializer',
			),
		),

		'wikibase.serialization.ClaimSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimSerializer',
			),
		),

		'wikibase.serialization.ClaimUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimUnserializer',
			),
		),

		'wikibase.serialization.EntityIdSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityIdSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityIdSerializer',
			),
		),

		'wikibase.serialization.EntityIdUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityIdUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityIdUnserializer',
			),
		),

		'wikibase.serialization.EntitySerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntitySerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntitySerializer',
			),
		),

		'wikibase.serialization.EntityUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityUnserializer',
			),
		),

		'wikibase.serialization.MultilingualSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.MultilingualSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.serialization.MultilingualSerializer',
			),
		),

		'wikibase.serialization.MultilingualUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.MultilingualUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.serialization.MultilingualUnserializer',
			),
		),

		'wikibase.serialization.ReferenceSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ReferenceSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceSerializer',
			),
		),

		'wikibase.serialization.ReferenceUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ReferenceUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceUnserializer',
			),
		),

		'wikibase.serialization.SiteLinkSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SiteLinkSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SiteLinkSerializer',
			),
		),

		'wikibase.serialization.SiteLinkUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SiteLinkUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SiteLinkUnserializer',
			),
		),

		'wikibase.serialization.Serializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.Serializer.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SerializerFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SerializerFactory.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SerializerFactory',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.SnakListSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakListSerializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SnakListSerializer',
			),
		),

		'wikibase.serialization.SnakListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SnakListUnserializer',
			),
		),

		'wikibase.serialization.SnakSerializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakSerializer.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'wikibase.datamodel',
				'wikibase.serialization.SnakSerializer',
			),
		),

		'wikibase.serialization.SnakUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakUnserializer.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'wikibase.datamodel',
				'wikibase.serialization.SnakUnserializer',
			),
		),

		'wikibase.serialization.Unserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.Unserializer.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Unserializer',
			),
		),

	);

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		$modules
	);


	return true;
};
