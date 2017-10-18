<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	ResourceLoader &$resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include __DIR__ . '/lib/resources.test.php',
		include __DIR__ . '/tests/qunit/resources.php'
	);

	return true;
};
