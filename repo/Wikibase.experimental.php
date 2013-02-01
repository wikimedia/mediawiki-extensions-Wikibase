<?php

/**
 * This file holds registration of experimental features part of the Wikibase extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WB_VERSION' ) ) {
	die( 'Not an entry point.' );
}
$dir = __DIR__ . '/';

$wgAutoloadClasses['Wikibase\Repo\Api\RemoveQualifiers'] 	= $dir . 'includes/api/RemoveQualifiers.php';
$wgAutoloadClasses['Wikibase\Repo\Api\SetQualifier'] 		= $dir . 'includes/api/SetQualifier.php';
$wgAutoloadClasses['Wikibase\Api\SetStatementRank']			= $dir . 'includes/api/SetStatementRank.php';
$wgAutoloadClasses['SpecialEntityData'] 					= $dir . 'includes/specials/SpecialEntityData.php';

$wgAPIModules['wbremovequalifiers'] 				= 'Wikibase\Repo\Api\RemoveQualifiers';
$wgAPIModules['wbsetqualifier'] 					= 'Wikibase\Repo\Api\SetQualifier';
$wgAPIModules['wbsetstatementrank'] 				= 'Wikibase\Api\SetStatementRank';

$wgSpecialPages['EntityData'] 						= 'SpecialEntityData';

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 0.3
 *
 * @param array &$files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][] = function( array &$files ) {
	// @codeCoverageIgnoreStart
	$testFiles = array(
		'specials/SpecialEntityData',
		'api/RemoveQualifiers',
		'api/SetStatementRank',
		'api/SetQualifier',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/phpunit/includes/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
