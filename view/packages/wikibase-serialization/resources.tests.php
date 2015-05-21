<?php

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
global $wgHooks;
$wgHooks['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	\ResourceLoader &$resourceLoader
) {

	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include( __DIR__ . '/tests/resources.php' )
	);

	return true;
};
