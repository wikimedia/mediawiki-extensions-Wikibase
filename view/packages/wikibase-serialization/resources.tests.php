<?php
/**
 * @codeCoverageIgnoreStart
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgHooks']['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	\ResourceLoader &$resourceLoader
) {

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include( __DIR__ . '/tests/resources.php' )
	);

	return true;
};
