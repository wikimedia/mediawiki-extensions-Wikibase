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

$wgAutoloadClasses['Wikibase\Api\CreateClaim'] 				= $dir . 'includes/api/CreateClaim.php';
$wgAutoloadClasses['Wikibase\Api\GetClaims'] 				= $dir . 'includes/api/GetClaims.php';
$wgAutoloadClasses['Wikibase\Api\RemoveClaims'] 			= $dir . 'includes/api/RemoveClaims.php';
$wgAutoloadClasses['Wikibase\Api\SetClaimValue'] 			= $dir . 'includes/api/SetClaimValue.php';
$wgAutoloadClasses['Wikibase\Api\SetReference'] 			= $dir . 'includes/api/SetReference.php';
$wgAutoloadClasses['Wikibase\Api\RemoveQualifiers'] 		= $dir . 'includes/api/RemoveQualifiers.php';
$wgAutoloadClasses['Wikibase\Api\RemoveReferences'] 		= $dir . 'includes/api/RemoveReferences.php';
$wgAutoloadClasses['Wikibase\Api\SetQualifier'] 			= $dir . 'includes/api/SetQualifier.php';
$wgAutoloadClasses['Wikibase\Api\SetStatementRank']			= $dir . 'includes/api/SetStatementRank.php';


$wgAutoloadClasses['SpecialListDatatypes'] 				= $dir . 'includes/specials/SpecialListDatatypes.php';
$wgAutoloadClasses['SpecialNewProperty'] 				= $dir . 'includes/specials/SpecialNewProperty.php';
$wgAutoloadClasses['SpecialEntityData'] 				= $dir . 'includes/specials/SpecialEntityData.php';

$wgAPIModules['wbcreateclaim'] 						= 'Wikibase\Api\CreateClaim';
$wgAPIModules['wbgetclaims'] 						= 'Wikibase\Api\GetClaims';
$wgAPIModules['wbremoveclaims'] 					= 'Wikibase\Api\RemoveClaims';
$wgAPIModules['wbsetclaimvalue'] 					= 'Wikibase\Api\SetClaimValue';
$wgAPIModules['wbsetreference'] 					= 'Wikibase\Api\SetReference';
$wgAPIModules['wbremovequalifiers'] 				= 'Wikibase\Api\RemoveQualifiers';
$wgAPIModules['wbremovereferences'] 				= 'Wikibase\Api\RemoveReferences';
$wgAPIModules['wbsetqualifier'] 					= 'Wikibase\Api\SetQualifier';
$wgAPIModules['wbsetstatementrank'] 				= 'Wikibase\Api\SetStatementRank';

$wgSpecialPages['EntityData'] 						= 'SpecialEntityData';
$wgSpecialPages['NewProperty'] 						= 'SpecialNewProperty';
$wgSpecialPages['ListDatatypes']        			= 'SpecialListDatatypes';

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

		'api/CreateClaim',
		'api/GetClaims',
		'api/RemoveClaims',
		'api/SetClaimValue',
		'api/SetReference',
		'api/RemoveQualifiers',
		'api/RemoveReferences',
		'api/SetStatementRank',
		'api/SetQualifier',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/phpunit/includes/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};
