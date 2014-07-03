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
		'localBasePath' => __DIR__,
		'remoteExtPath' => '../' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . $remoteExtPathParts[3],
	);

	// FIXME: No tests here
	$testModules['qunit']['wikibase.serialization.tests'] = $moduleTemplate + array(
		'scripts' => array(
		),
		'dependencies' => array(
		)
	);

	return true;
};
