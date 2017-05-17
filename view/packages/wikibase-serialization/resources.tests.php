<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	ResourceLoader &$resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include __DIR__ . '/tests/resources.php'
	);
};
