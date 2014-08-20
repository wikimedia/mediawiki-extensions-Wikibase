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
		'localBasePath' => __DIR__ . DIRECTORY_SEPARATOR . 'tests',
		'remoteExtPath' => '..' . $remoteExtPathParts[2] . DIRECTORY_SEPARATOR . 'tests',
	);

	$testModules['qunit']['wikibase.datamodel.tests'] = $moduleTemplate + array(
		'scripts' => array(
			'Claim.tests.js',
			'Item.tests.js',
			'Property.tests.js',
			'Reference.tests.js',
			'SiteLink.tests.js',
			'Snak.tests.js',
			'SnakList.tests.js',
			'Statement.tests.js',
		),
		'dependencies' => array(
			'wikibase.datamodel',
			'wikibase.datamodel.tests.testEntity',
			'wikibase.tests.qunit.testrunner',
		)
	);

	$testModules['qunit']['wikibase.datamodel.tests.testEntity'] = $moduleTemplate + array(
		'scripts' => array(
			'testEntity.js',
		),
		'dependencies' => array(
			'wikibase.datamodel',
		)
	);

	return true;
};
