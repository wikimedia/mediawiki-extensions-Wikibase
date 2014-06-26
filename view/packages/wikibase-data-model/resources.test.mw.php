<?php

global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
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

	$testModules['qunit']['wikibase.datamodel.tests'] = $moduleTemplate + array(
		'scripts' => array(
			'tests/Wikibase.claim.tests.js',
			'tests/Wikibase.reference.tests.js',
			'tests/Wikibase.snak.tests.js',
			'tests/Wikibase.SnakList.tests.js',
			'tests/wikibase.Statement.tests.js',
			'tests/datamodel.Entity.tests.js',
			'tests/datamodel.Item.tests.js',
			'tests/datamodel.Property.tests.js',
		),
		'dependencies' => array(
			'wikibase.tests.qunit.testrunner',
		)
	);

	return true;
};
