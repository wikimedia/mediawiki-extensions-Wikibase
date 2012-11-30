<?php

/**
 * Initialization file for EXPERIMENTAL features of the Wikibase extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
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

$wgHooks['UnitTestsList'][] 						= 'Wikibase\RepoHooks::registerExperimentalUnitTests';
