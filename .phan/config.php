<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['scalar_implicit_cast'] = true;
$cfg['null_casts_as_any_type'] = true;

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'client/WikibaseClient.datatypes.php',
		'client/WikibaseClient.i18n.alias.php',
		'client/WikibaseClient.i18n.magic.php',
		'lib/config/WikibaseLib.default.php',
		'lib/WikibaseLib.datatypes.php',
		'lib/WikibaseLib.entitytypes.php',
		'repo/config/Wikibase.default.php',
		'repo/config/Wikibase.searchindex.php',
		'repo/Wikibase.i18n.alias.php',
		'repo/Wikibase.i18n.namespaces.php',
		'repo/WikibaseRepo.datatypes.php',
		'repo/WikibaseRepo.entitytypes.php',
		'view/resources.php',
		'Wikibase.php',
	]
);

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'data-access/src',
		'client/includes',
		'repo/includes',
		'repo/rest-api/src',
		'lib/includes',
		'client/maintenance',
		'repo/maintenance',
		'lib/maintenance',
		'view/src',
		'lib/packages/wikibase/changes/src',
		'lib/packages/wikibase/federated-properties/src',
		'lib/packages/wikibase/data-model/src/',
		'lib/packages/wikibase/data-model-serialization/src/',
		'lib/packages/wikibase/data-model-services/src/',
		'lib/packages/wikibase/internal-serialization/src/',
		'../../extensions/Babel/',
		'../../extensions/CirrusSearch/',
		'../../extensions/Echo/',
		'../../extensions/GeoData/',
		'../../extensions/Math/',
		'../../extensions/MobileFrontend/',
		'../../extensions/PageImages/',
		'../../extensions/Scribunto/',
	]
);

if ( is_dir( 'vendor' ) ) {
	$cfg['directory_list'][] = 'vendor';
	$cfg['exclude_analysis_directory_list'][] = 'vendor';
}

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'lib/packages/wikibase/data-model/src/',
		'lib/packages/wikibase/data-model-serialization/src/',
		'lib/packages/wikibase/data-model-services/src/',
		'lib/packages/wikibase/internal-serialization/src/',
		'../../extensions/Babel/',
		'../../extensions/CirrusSearch/',
		'../../extensions/Echo/',
		'../../extensions/GeoData/',
		'../../extensions/Math/',
		'../../extensions/MobileFrontend/',
		'../../extensions/PageImages/',
		'../../extensions/Scribunto/',
	]
);

/*
 * NOTE: adding things here should be meant as a last resort.
 * Inline, method-docblock or file-wide suppression is to be preferred.
 */
$cfg['suppress_issue_types'] = array_merge(
	$cfg['suppress_issue_types'],
	[
		// Disabled when upgrading to 0.11.1
		'PhanPluginNeverReturnMethod',
		'PhanPluginSimplifyExpressionBool',
		'PhanTypeMismatchReturn',
		'PhanTypeMismatchArgumentSuperType',
		'PhanTypeMismatchReturnSuperType',
		'PhanPluginUnreachableCode',

		// Both local and global vendor directories have to be analysed
		"PhanRedefinedClassReference",
		"PhanRedefinedExtendedClass",
		"PhanRedefinedInheritedInterface",
		"PhanRedefinedUsedTrait",
	]
);

return $cfg;
