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

		'wikibase.serialization.ClaimsUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ClaimsUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ClaimsUnserializer',
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

		'wikibase.serialization.EntityUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.EntityUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.EntityUnserializer',
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

		'wikibase.serialization.ReferenceUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.ReferenceUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.ReferenceUnserializer',
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

		'wikibase.serialization.SnakListUnserializer.tests' => $moduleTemplate + array(
			'scripts' => array(
				'serialization.SnakListUnserializer.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.SnakListUnserializer',
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
