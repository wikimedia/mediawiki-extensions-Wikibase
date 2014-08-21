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

	// FIXME: Add tests for all components
	$testModules['qunit']['wikibase.serialization.MultilingualUnserializer.tests'] = $moduleTemplate + array(
		'scripts' => array(
			'serialization.MultilingualUnserializer.tests.js',
		),
		'dependencies' => array(
			'wikibase.serialization.MultilingualUnserializer',
		),
	);

	return true;
};
