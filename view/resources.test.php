<?php

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
global $wgHooks;
$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, ResourceLoader &$resourceLoader ) {

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include 'tests/qunit/resources.php'
	);

	return true;
};
