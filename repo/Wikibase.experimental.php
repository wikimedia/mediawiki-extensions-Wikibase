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

$wgAutoloadClasses['Wikibase\ApiCreateClaim'] 			= $dir . 'includes/api/ApiCreateClaim.php';
$wgAutoloadClasses['Wikibase\ApiGetClaims'] 			= $dir . 'includes/api/ApiGetClaims.php';
$wgAutoloadClasses['Wikibase\ApiRemoveClaims'] 			= $dir . 'includes/api/ApiRemoveClaims.php';
$wgAutoloadClasses['Wikibase\ApiSetClaimValue'] 		= $dir . 'includes/api/ApiSetClaimValue.php';
$wgAutoloadClasses['Wikibase\ApiSetReference'] 			= $dir . 'includes/api/ApiSetReference.php';
$wgAutoloadClasses['Wikibase\Api\RemoveReferences'] 	= $dir . 'includes/api/RemoveReferences.php';
$wgAutoloadClasses['Wikibase\Api\SetQualifier'] 		= $dir . 'includes/api/SetQualifier.php';
$wgAutoloadClasses['Wikibase\Api\SetStatementRank'] 	= $dir . 'includes/api/SetStatementRank.php';


$wgAutoloadClasses['SpecialListDatatypes'] 				= $dir . 'includes/specials/SpecialListDatatypes.php';
$wgAutoloadClasses['SpecialNewProperty'] 				= $dir . 'includes/specials/SpecialNewProperty.php';
$wgAutoloadClasses['SpecialEntityData'] 				= $dir . 'includes/specials/SpecialEntityData.php';

$wgAPIModules['wbcreateclaim'] 						= 'Wikibase\ApiCreateClaim';
$wgAPIModules['wbgetclaims'] 						= 'Wikibase\ApiGetClaims';
$wgAPIModules['wbremoveclaims'] 					= 'Wikibase\ApiRemoveClaims';
$wgAPIModules['wbsetclaimvalue'] 					= 'Wikibase\ApiSetClaimValue';
$wgAPIModules['wbsetreference'] 					= 'Wikibase\ApiSetReference';
$wgAPIModules['wbremovereferences'] 				= 'Wikibase\Api\RemoveReferences';
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

		'api/ApiCreateClaim',
		'api/ApiGetClaims',
		'api/ApiRemoveClaims',
		'api/ApiSetClaimValue',
		'api/ApiSetReference',
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
