<?php

global $wgHooks;

$wgHooks['ResourceLoaderTestModules'][] = function( array &$testModules, \ResourceLoader &$resourceLoader ) {
	preg_match(
		'+^(.*?)(' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPathParts[2],
	);

	$testModules['qunit']['wikibase.datamodel.tests'] = $moduleTemplate + array(
		'scripts' => array(
			'tests/Claim.tests.js',
			'tests/Entity.tests.js',
			'tests/Item.tests.js',
			'tests/Property.tests.js',
			'tests/Reference.tests.js',
			'tests/Snak.tests.js',
			'tests/SnakList.tests.js',
			'tests/Statement.tests.js',
		),
		'dependencies' => array(
			'wikibase.datamodel',
			'wikibase.tests.qunit.testrunner',
		)
	);

	return true;
};
