<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'client/WikibaseClient.datatypes.php',
		'client/ClientHooks.php',
		'client/WikibaseClient.i18n.alias.php',
		'client/WikibaseClient.i18n.magic.php',
		'client/WikibaseClient.php',
		'lib/config/WikibaseLib.default.php',
		'lib/WikibaseLib.datatypes.php',
		'lib/WikibaseLib.entitytypes.php',
		'lib/LibHooks.php',
		'lib/WikibaseLib.php',
		'repo/config/Wikibase.default.php',
		'repo/config/Wikibase.searchindex.php',
		'repo/RepoHooks.php',
		'repo/Wikibase.i18n.alias.php',
		'repo/Wikibase.i18n.namespaces.php',
		'repo/Wikibase.php',
		'repo/WikibaseRepo.datatypes.php',
		'repo/WikibaseRepo.entitytypes.php',
		'view/resources.php',
		'view/ViewHooks.php',
		'view/WikibaseView.php',
		'Wikibase.php',
		// Include extension stubs so we don't require extensions to be available locally.
		'.phan/stubs/babel.php',
		'.phan/stubs/cirrussearch.php',
		'.phan/stubs/echo.php',
		'.phan/stubs/geodata.php',
		'.phan/stubs/math.php',
		'.phan/stubs/mobilefrontend.php',
		'.phan/stubs/monolog.php',
		'.phan/stubs/pageimages.php',
		'.phan/stubs/scribunto.php',
	]
);

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'data-access/src',
		'client/includes',
		'repo/includes',
		'lib/includes',
		'client/maintenance',
		'repo/maintenance',
		'lib/maintenance',
		'view/src',
		'../../includes',
		'../../languages',
		'../../maintenance',
		'../../vendor',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'.phan/stubs',
		'../../includes',
		'../../languages',
		'../../maintenance',
		'../../vendor',
		'../../extensions',
	]
);

if ( is_dir( 'vendor' ) ) {
	$cfg['directory_list'][] = 'vendor';
	$cfg['exclude_analysis_directory_list'][] = 'vendor';
}

$cfg['suppress_issue_types'] = array_merge(
	$cfg['suppress_issue_types'],
	[
		// approximate error count: 6
		"PhanDeprecatedClass",
		// approximate error count: 107
		"PhanDeprecatedFunction",
		// approximate error count: 3
		"PhanParamReqAfterOpt",
		// approximate error count: 9
		"PhanParamSignatureMismatch",
		// approximate error count: 1
		"PhanParamSignatureMismatchInternal",
		// approximate error count: 4
		"PhanParamTooMany",
		// approximate error count: 2
		"PhanTypeComparisonToArray",
		// approximate error count: 47
		"PhanTypeMismatchArgument",
		// approximate error count: 5
		"PhanTypeMismatchArgumentInternal",
		// approximate error count: 3
		"PhanTypeMismatchProperty",
		// approximate error count: 2
		"PhanTypeMismatchReturn",
		// approximate error count: 1
		"PhanTypeMissingReturn",
		// approximate error count: 72
		"PhanUndeclaredConstant",
		// approximate error count: 168
		"PhanUndeclaredMethod",
		// approximate error count: 17
		"PhanUndeclaredProperty",
		// approximate error count: 4
		"PhanUndeclaredVariable",
		// approximate error count: 1
		"PhanTypeArraySuspicious",

		"PhanAccessClassConstantInternal",
		"PhanDeprecatedInterface",
		"PhanNonClassMethodCall",
		"PhanTypeArraySuspiciousNullable",
		"PhanTypeInvalidExpressionArrayDestructuring",
		"PhanTypeInvalidThrowsIsInterface",
		"PhanTypeMismatchDeclaredParam",
		"PhanTypeMismatchDimFetch",
		"PhanTypeNoPropertiesForeach",
		"PhanUndeclaredInvokeInCallable",

		// This extension uses weird parameter descriptions that would be addressed by migrating to 7.2
		"PhanUnextractableAnnotation",
		"PhanUnextractableAnnotationElementName",
		"PhanUnextractableAnnotationSuffix",

		// Phan doesn't understand /** @var Classname */ annotations
		"PhanUnreferencedUseNormal",

		// Both local and global vendor directories have to be analysed
		"PhanRedefinedExtendedClass",
		"PhanRedefinedInheritedInterface",
		"PhanRedefinedUsedTrait",
	]
);

return $cfg;
