<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
global $wgHooks;
$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include 'tests/qunit/resources.php'
	);

	return true;
};
